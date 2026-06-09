<?php

use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\AdmissionStatusType;
use App\Enums\Audit\ActivityLogSeverity;
use App\Enums\ExamStatus;
use App\Enums\PriceLists\PriceType;
use App\Enums\TermType;
use App\Helpers\ExamAttemptFileHandler;
use App\Models\AcademicSession;
use App\Models\ActivityLog;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Question;
use App\Models\ResultPublication;
use App\Models\Student;
use App\Models\TermResult;
use App\Support\ExamHandler;
use App\Support\SettingsHandler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
  SettingsHandler::clear();
  ClassResultInfo::clearResultLockCache();

  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
});

afterEach(function () {
  SettingsHandler::clear();
});

it(
  'logs result score recording and updates with compact metadata',
  function () {
    $classification = Classification::factory()
      ->withInstitution($this->institution)
      ->create();
    $course = Course::factory()
      ->withInstitution($this->institution)
      ->create();
    $student = Student::factory()
      ->withInstitution($this->institution, $classification)
      ->create();
    $courseTeacher = CourseTeacher::factory()->create([
      'institution_id' => $this->institution->id,
      'course_id' => $course->id,
      'classification_id' => $classification->id,
      'user_id' => $this->admin->id
    ]);

    ActivityLog::query()->delete();

    actingAs($this->admin);
    RecordCourseResult::run(
      [
        'institution_id' => $this->institution->id,
        'student_id' => $student->id,
        'academic_session_id' => $this->academicSession->id,
        'term' => TermType::First->value,
        'for_mid_term' => false,
        'exam' => 72
      ],
      $courseTeacher
    );

    $recordedLog = ActivityLog::query()
      ->where('event', 'result.score_recorded')
      ->firstOrFail();

    expect($recordedLog->actor_id)
      ->toBe($this->admin->id)
      ->and($recordedLog->institution_id)
      ->toBe($this->institution->id)
      ->and($recordedLog->subject_type)
      ->toBe(\App\Models\CourseResult::class)
      ->and($recordedLog->properties->toArray())
      ->toMatchArray([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $this->academicSession->id,
        'term' => TermType::First->value,
        'result' => 72
      ]);

    RecordCourseResult::run(
      [
        'institution_id' => $this->institution->id,
        'student_id' => $student->id,
        'academic_session_id' => $this->academicSession->id,
        'term' => TermType::First->value,
        'for_mid_term' => false,
        'exam' => 81
      ],
      $courseTeacher
    );

    $updatedLog = ActivityLog::query()
      ->where('event', 'result.score_updated')
      ->firstOrFail();

    expect($updatedLog->severity)
      ->toBe(ActivityLogSeverity::Warning->value)
      ->and($updatedLog->old_values->toArray())
      ->toMatchArray(['result' => 72])
      ->and($updatedLog->new_values->toArray())
      ->toMatchArray(['result' => 81]);
  }
);

it('logs result locking and unlocking as high-impact events', function () {
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $classResultInfo = ClassResultInfo::factory()
    ->classification($classification)
    ->create([
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'is_locked' => true
    ]);

  ActivityLog::query()->delete();

  actingAs($this->admin)
    ->postJson(
      route('institutions.class-result-info.lock', [
        $this->institution->uuid,
        $classResultInfo
      ]),
      ['is_locked' => false]
    )
    ->assertOk();

  $unlockLog = ActivityLog::query()
    ->where('event', 'result.unlocked')
    ->firstOrFail();

  expect($unlockLog->actor_id)
    ->toBe($this->admin->id)
    ->and($unlockLog->severity)
    ->toBe(ActivityLogSeverity::Critical->value)
    ->and($unlockLog->institution_id)
    ->toBe($this->institution->id)
    ->and($unlockLog->properties->toArray())
    ->toMatchArray([
      'class_result_info_id' => $classResultInfo->id,
      'classification_id' => $classification->id
    ]);

  actingAs($this->admin)
    ->postJson(
      route('institutions.class-result-info.lock', [
        $this->institution->uuid,
        $classResultInfo
      ]),
      ['is_locked' => true]
    )
    ->assertOk();

  expect(
    ActivityLog::query()
      ->where('event', 'result.locked')
      ->firstOrFail()
      ->new_values->toArray()
  )->toMatchArray(['is_locked' => true]);
});

it('logs result publication summaries', function () {
  InstitutionSetting::factory()
    ->academicSession($this->institution, $this->academicSession)
    ->create();
  InstitutionSetting::factory()
    ->term($this->institution, TermType::First->value)
    ->create();
  SettingsHandler::clear();

  $classification = Classification::factory()
    ->for($this->institution)
    ->create();
  $priceList = $this->institution->institutionGroup
    ->priceLists()
    ->where('type', PriceType::ResultChecking)
    ->first();
  $priceList->update(['amount' => 0]);

  $termResults = TermResult::factory(2)
    ->for($this->academicSession)
    ->withInstitution($this->institution)
    ->create([
      'classification_id' => $classification->id,
      'term' => TermType::First->value,
      'for_mid_term' => false
    ]);

  ActivityLog::query()->delete();

  actingAs($this->admin);
  postJson(
    route('institutions.result-publications.store', $this->institution),
    [
      'classifications' => [$classification->id]
    ]
  )->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'result.published')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($this->admin->id)
    ->and($log->institution_id)
    ->toBe($this->institution->id)
    ->and($log->subject_type)
    ->toBe(ResultPublication::class)
    ->and($log->severity)
    ->toBe(ActivityLogSeverity::Critical->value)
    ->and($log->properties->toArray())
    ->toMatchArray([
      'published_result_count' => $termResults->count(),
      'classification_ids' => [$classification->id],
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First->value
    ]);
});

it(
  'logs CBT exam submission without storing attempts in the explicit log',
  function () {
    $event = Event::factory()
      ->institution($this->institution)
      ->started()
      ->eventCourseables(1)
      ->create();
    $student = Student::factory()
      ->withInstitution($this->institution)
      ->create();
    $exam = Exam::factory()
      ->started()
      ->examable($student)
      ->event($event)
      ->create(['status' => ExamStatus::Active]);
    $examCourseable = ExamCourseable::factory()
      ->exam($exam)
      ->courseable($event->eventCourseables->first()->courseable)
      ->create();
    $questions = Question::factory(2)
      ->courseable($examCourseable->courseable)
      ->create();
    $attemptFile = ExamAttemptFileHandler::make($exam);
    $attemptFile->syncExamFile();
    $attemptFile->attemptQuestion(
      $questions
        ->mapWithKeys(fn($question) => [$question->id => $question->answer])
        ->toArray()
    );

    ActivityLog::query()->delete();
    auth()->logout();

    ExamHandler::make($exam)->endExam();

    $log = ActivityLog::query()
      ->where('event', 'exam.submitted')
      ->firstOrFail();

    expect($log->actor_id)
      ->toBeNull()
      ->and($log->institution_id)
      ->toBe($this->institution->id)
      ->and($log->subject_type)
      ->toBe(Exam::class)
      ->and($log->properties->toArray())
      ->toMatchArray([
        'exam_id' => $exam->id,
        'score' => 2,
        'num_of_questions' => 2
      ])
      ->and(array_key_exists('attempts', $log->properties->toArray()))
      ->toBeFalse()
      ->and($log->old_values)
      ->toBeNull()
      ->and($log->new_values)
      ->toBeNull();

    if (File::exists($attemptFile->getFullFilepath())) {
      File::delete($attemptFile->getFullFilepath());
    }
  }
);

it(
  'logs public admission application submissions with no authenticated actor',
  function () {
    $admissionForm = AdmissionForm::factory()->create([
      'institution_id' => $this->institution->id,
      'academic_session_id' => $this->academicSession->id,
      'price' => 0
    ]);
    $payload = AdmissionApplication::factory()
      ->admissionForm($admissionForm)
      ->make()
      ->toArray();

    ActivityLog::query()->delete();
    auth()->logout();

    postJson(
      route('institutions.admissions.store', [
        'institution' => $this->institution->uuid
      ]),
      collect($payload)
        ->except('photo', 'name', 'photo_url')
        ->toArray()
    )->assertOk();

    $log = ActivityLog::query()
      ->where('event', 'admission.application_submitted')
      ->firstOrFail();

    expect($log->actor_id)
      ->toBeNull()
      ->and($log->institution_id)
      ->toBe($this->institution->id)
      ->and($log->subject_type)
      ->toBe(AdmissionApplication::class)
      ->and($log->properties->toArray())
      ->toMatchArray([
        'reference' => $payload['reference'],
        'admission_form_id' => $admissionForm->id,
        'academic_session_id' => $this->academicSession->id
      ])
      ->and(array_key_exists('photo', $log->properties->toArray()))
      ->toBeFalse();
  }
);

it('logs admission approval and rejection events', function () {
  $admissionForm = AdmissionForm::factory()->create([
    'institution_id' => $this->institution->id,
    'academic_session_id' => $this->academicSession->id
  ]);
  $classification = Classification::factory()
    ->for($this->institution)
    ->create();
  $approvedApplication = AdmissionApplication::factory()
    ->admissionForm($admissionForm)
    ->create(['admission_status' => AdmissionStatusType::Pending->value]);
  $rejectedApplication = AdmissionApplication::factory()
    ->admissionForm($admissionForm)
    ->create(['admission_status' => AdmissionStatusType::Pending->value]);

  ActivityLog::query()->delete();

  actingAs($this->admin)
    ->postJson(
      route('institutions.admission-applications.update-status', [
        $this->institution->uuid,
        $approvedApplication
      ]),
      [
        'admission_status' => AdmissionStatusType::Admitted->value,
        'classification' => $classification->id
      ]
    )
    ->assertOk();

  actingAs($this->admin)
    ->postJson(
      route('institutions.admission-applications.update-status', [
        $this->institution->uuid,
        $rejectedApplication
      ]),
      [
        'admission_status' => AdmissionStatusType::Declined->value,
        'classification' => $classification->id
      ]
    )
    ->assertOk();

  $approvedLog = ActivityLog::query()
    ->where('event', 'admission.application_approved')
    ->firstOrFail();
  $rejectedLog = ActivityLog::query()
    ->where('event', 'admission.application_rejected')
    ->firstOrFail();

  expect($approvedLog->actor_id)
    ->toBe($this->admin->id)
    ->and($approvedLog->severity)
    ->toBe(ActivityLogSeverity::Critical->value)
    ->and($approvedLog->properties->toArray())
    ->toMatchArray([
      'admission_application_id' => $approvedApplication->id,
      'classification_id' => $classification->id
    ])
    ->and($approvedLog->new_values->toArray())
    ->toMatchArray(['admission_status' => AdmissionStatusType::Admitted->value])
    ->and($rejectedLog->new_values->toArray())
    ->toMatchArray([
      'admission_status' => AdmissionStatusType::Declined->value
    ]);
});
