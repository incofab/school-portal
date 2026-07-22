<?php

use App\Enums\AttendanceType;
use App\Enums\GuardianRelationship;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\TermDetail;
use App\Models\User;
use App\Support\SettingsHandler;
use Carbon\Carbon;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  SettingsHandler::clear();
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  ActivityLog::query()->delete();
});

afterEach(function () {
  Carbon::setTestNow();
});

it(
  'logs student code changes without duplicate model update logs',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution)
      ->create(['code' => 'OLD-CODE']);

    actingAs($this->admin)
      ->postJson(
        route('institutions.students.update-code', [
          $this->institution->uuid,
          $student
        ]),
        ['code' => 'NEW-CODE']
      )
      ->assertOk();

    $log = ActivityLog::query()
      ->where('event', 'student.code_changed')
      ->firstOrFail();

    expect($log->actor_id)
      ->toBe($this->admin->id)
      ->and($log->institution_id)
      ->toBe($this->institution->id)
      ->and($log->subject_type)
      ->toBe(Student::class)
      ->and($log->subject_id)
      ->toBe($student->id)
      ->and($log->old_values['code'])
      ->toBe('OLD-CODE')
      ->and($log->new_values['code'])
      ->toBe('NEW-CODE')
      ->and(
        ActivityLog::query()
          ->where('event', 'model.student.updated')
          ->exists()
      )
      ->toBeFalse();
  }
);

it('logs student class changes with movement metadata', function () {
  $sourceClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $destinationClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $sourceClass)
    ->create();

  actingAs($this->admin)
    ->postJson(
      route('institutions.students.change-class', [
        $this->institution->uuid,
        $student
      ]),
      ['destination_class' => $destinationClass->id]
    )
    ->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'student.class_changed')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($this->admin->id)
    ->and($log->institution_id)
    ->toBe($this->institution->id)
    ->and($log->subject_type)
    ->toBe(Student::class)
    ->and($log->subject_id)
    ->toBe($student->id)
    ->and($log->properties->toArray())
    ->toMatchArray([
      'student_id' => $student->id,
      'source_classification_id' => $sourceClass->id,
      'destination_classification_id' => $destinationClass->id
    ]);
});

it('logs class migration as a compact summary event', function () {
  $sourceClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $destinationClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  Student::factory()
    ->count(3)
    ->withInstitution($this->institution, $sourceClass)
    ->create();

  actingAs($this->admin)
    ->postJson(
      route('institutions.classifications.migrate-students', [
        $this->institution->uuid,
        $sourceClass
      ]),
      ['destination_class' => $destinationClass->id]
    )
    ->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'student.migrated')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($this->admin->id)
    ->and($log->institution_id)
    ->toBe($this->institution->id)
    ->and($log->subject_type)
    ->toBe(Classification::class)
    ->and($log->subject_id)
    ->toBe($sourceClass->id)
    ->and($log->properties->toArray())
    ->toMatchArray([
      'student_count' => 3,
      'source_classification_id' => $sourceClass->id,
      'destination_classification_id' => $destinationClass->id
    ]);
});

it('logs guardian assignment and dependent removal', function () {
  $student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $guardian = User::factory()->create();
  $guardian
    ->institutions()
    ->syncWithPivotValues(
      [$this->institution->id],
      ['role' => InstitutionUserType::Guardian]
    );

  actingAs($this->admin)
    ->postJson(
      route('institutions.guardians.assign-student', [
        $this->institution->uuid,
        $guardian
      ]),
      [
        'student_id' => $student->id,
        'relationship' => GuardianRelationship::Parent->value
      ]
    )
    ->assertOk();

  $assignedLog = ActivityLog::query()
    ->where('event', 'guardian.assigned')
    ->firstOrFail();

  expect($assignedLog->actor_id)
    ->toBe($this->admin->id)
    ->and($assignedLog->institution_id)
    ->toBe($this->institution->id)
    ->and($assignedLog->subject_type)
    ->toBe(Student::class)
    ->and($assignedLog->subject_id)
    ->toBe($student->id)
    ->and($assignedLog->properties->toArray())
    ->toMatchArray([
      'student_id' => $student->id,
      'guardian_user_id' => $guardian->id,
      'relationship' => GuardianRelationship::Parent->value
    ]);

  actingAs($guardian)
    ->deleteJson(
      route('institutions.guardians.remove-dependent', [
        $this->institution->uuid,
        $student
      ])
    )
    ->assertOk();

  $removedLog = ActivityLog::query()
    ->where('event', 'guardian.dependent_removed')
    ->firstOrFail();

  expect($removedLog->actor_id)
    ->toBe($guardian->id)
    ->and($removedLog->institution_id)
    ->toBe($this->institution->id)
    ->and($removedLog->subject_id)
    ->toBe($student->id)
    ->and(GuardianStudent::query()->count())
    ->toBe(0);
});

it('logs course teacher assignments', function () {
  $teacher = User::factory()->create();
  InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $teacher->id,
    'role' => InstitutionUserType::Teacher->value
  ]);
  $course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->postJson(
      route('institutions.course-teachers.store', [
        $this->institution->uuid,
        $teacher
      ]),
      [
        'course_ids' => [$course->id],
        'classification_ids' => [$classification->id]
      ]
    )
    ->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'course.teacher_assigned')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($this->admin->id)
    ->and($log->institution_id)
    ->toBe($this->institution->id)
    ->and($log->subject_type)
    ->toBe(Course::class)
    ->and($log->subject_id)
    ->toBe($course->id)
    ->and($log->properties->toArray())
    ->toMatchArray([
      'course_id' => $course->id,
      'teacher_user_id' => $teacher->id,
      'classification_id' => $classification->id
    ]);
});

it('logs assignment scoring with student and course metadata', function () {
  $teacher = User::factory()->create();
  $teacherInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $teacher->id,
    'role' => InstitutionUserType::Teacher->value
  ]);
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  CourseTeacher::factory()->create([
    'institution_id' => $this->institution->id,
    'course_id' => $course->id,
    'classification_id' => $classification->id,
    'user_id' => $teacher->id
  ]);
  $assignment = Assignment::factory()
    ->withClassifications(collect([$classification]))
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $teacherInstitutionUser->id,
      'course_id' => $course->id,
      'max_score' => 20
    ]);
  $student = Student::factory()
    ->withInstitution($this->institution, $classification)
    ->create();
  $submission = AssignmentSubmission::factory()->create([
    'assignment_id' => $assignment->id,
    'student_id' => $student->id,
    'score' => null
  ]);

  actingAs($teacher)
    ->postJson(
      route('institutions.assignment-submission.score', [
        $this->institution->uuid,
        $submission
      ]),
      ['score' => 17, 'remark' => 'Good work']
    )
    ->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'assignment.scored')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($teacher->id)
    ->and($log->institution_id)
    ->toBe($this->institution->id)
    ->and($log->subject_type)
    ->toBe(Assignment::class)
    ->and($log->subject_id)
    ->toBe($assignment->id)
    ->and($log->properties->toArray())
    ->toMatchArray([
      'assignment_id' => $assignment->id,
      'assignment_submission_id' => $submission->id,
      'student_id' => $student->id,
      'course_id' => $course->id
    ])
    ->and($log->new_values->toArray())
    ->toMatchArray(['score' => 17]);
});

it('logs attendance record and update events', function () {
  $academicSession = AcademicSession::factory()->create();
  InstitutionSetting::factory()
    ->term($this->institution, TermType::First->value)
    ->create();
  InstitutionSetting::factory()
    ->academicSession($this->institution, $academicSession)
    ->create();
  TermDetail::factory()->create([
    'institution_id' => $this->institution->id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::First->value,
    'inactive_weekdays' => [],
    'special_active_days' => [],
    'inactive_days' => []
  ]);
  SettingsHandler::clear();
  Carbon::setTestNow(Carbon::parse('2026-06-08 08:00:00'));

  $staff = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  actingAs($this->admin)
    ->postJson(
      route('institutions.attendances.store', $this->institution->uuid),
      [
        'institution_user_id' => $staff->id,
        'reference' => Str::orderedUuid()->toString(),
        'type' => AttendanceType::In->value,
        'remark' => 'Morning'
      ]
    )
    ->assertOk();

  $recordedLog = ActivityLog::query()
    ->where('event', 'attendance.recorded')
    ->firstOrFail();

  expect($recordedLog->actor_id)
    ->toBe($this->admin->id)
    ->and($recordedLog->institution_id)
    ->toBe($this->institution->id)
    ->and($recordedLog->subject_type)
    ->toBe(Attendance::class)
    ->and($recordedLog->properties->toArray())
    ->toMatchArray([
      'institution_user_id' => $staff->id,
      'attendee_user_id' => $staff->user_id
    ]);

  Carbon::setTestNow(Carbon::parse('2026-06-08 15:00:00'));
  actingAs($this->admin)
    ->postJson(
      route('institutions.attendances.store', $this->institution->uuid),
      [
        'institution_user_id' => $staff->id,
        'type' => AttendanceType::Out->value,
        'remark' => 'Closed'
      ]
    )
    ->assertOk();

  $updatedLog = ActivityLog::query()
    ->where('event', 'attendance.updated')
    ->firstOrFail();

  expect($updatedLog->actor_id)
    ->toBe($this->admin->id)
    ->and($updatedLog->institution_id)
    ->toBe($this->institution->id)
    ->and($updatedLog->properties->toArray())
    ->toMatchArray([
      'institution_user_id' => $staff->id,
      'attendee_user_id' => $staff->user_id
    ])
    ->and($updatedLog->new_values->toArray())
    ->toHaveKey('signed_out_at');
});
