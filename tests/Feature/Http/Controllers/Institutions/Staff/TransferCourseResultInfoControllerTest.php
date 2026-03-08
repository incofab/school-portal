<?php

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;

  $this->academicSession = AcademicSession::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->teacher = User::factory()->teacher($this->institution)->create();
  $this->courseTeacher = CourseTeacher::factory()->create([
    'institution_id' => $this->institution->id,
    'course_id' => $this->course->id,
    'classification_id' => $this->classification->id,
    'user_id' => $this->teacher->id
  ]);

  $this->sourceAssessment = Assessment::factory()->create([
    'institution_id' => $this->institution->id,
    'term' => TermType::First->value,
    'for_mid_term' => false,
    'title' => 'ca_1',
    'max' => 20
  ]);
  $this->sourceAssessment->classifications()->sync([
    $this->classification->id
  ]);
  $this->targetAssessment = Assessment::factory()->create([
    'institution_id' => $this->institution->id,
    'term' => TermType::Second->value,
    'for_mid_term' => true,
    'title' => 'ca_1',
    'max' => 20
  ]);
  $this->targetAssessment->classifications()->sync([
    $this->classification->id
  ]);

  $this->students = Student::factory()
    ->count(2)
    ->withInstitution($this->institution, $this->classification)
    ->create();

  foreach ($this->students as $student) {
    $courseResult = CourseResult::factory()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => $this->teacher->id,
      'course_id' => $this->course->id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First,
      'for_mid_term' => false
    ]);
    $courseResult->fill([
      'exam' => 5,
      'assessment_values' => [
        $this->sourceAssessment->raw_title => 10
      ]
    ])->save();
  }

  EvaluateCourseResultForClass::run(
    $this->classification,
    $this->course->id,
    $this->academicSession->id,
    TermType::First->value,
    false
  );

  $this->courseResultInfo = CourseResultInfo::query()->firstOrFail();
});

test('admin can transfer course result info within the same session', function () {
  $targetAssessments = Assessment::getAssessments(
    TermType::Second->value,
    true,
    $this->classification->id
  );
  $assessmentMap = $targetAssessments
    ->mapWithKeys(fn($assessment) => [(string) $assessment->id => []])
    ->toArray();
  $assessmentMap[(string) $this->targetAssessment->id] = [
    $this->sourceAssessment->id
  ];
  $assessmentMap['exam'] = ['exam'];

  $response = actingAs($this->admin)->post(
    route('institutions.course-result-info.transfer.store', [
      $this->institution,
      $this->courseResultInfo
    ]),
    [
      'term' => TermType::Second->value,
      'for_mid_term' => true,
      'assessment_map' => $assessmentMap
    ]
  );

  $response->assertOk();

  expect(
    CourseResult::query()
      ->where('course_id', $this->course->id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', TermType::Second->value)
      ->where('for_mid_term', true)
      ->count()
  )->toBe(2);
  $targetResult = CourseResult::query()
    ->where('course_id', $this->course->id)
    ->where('classification_id', $this->classification->id)
    ->where('academic_session_id', $this->academicSession->id)
    ->where('term', TermType::Second->value)
    ->where('for_mid_term', true)
    ->first();
  expect($targetResult?->assessment_values[$this->targetAssessment->raw_title] ?? null)
    ->toBe(10)
    ->and($targetResult?->exam)
    ->toBe(5.0);

  expect(
    CourseResultInfo::query()
      ->where('course_id', $this->course->id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', TermType::Second->value)
      ->where('for_mid_term', true)
      ->exists()
  )->toBeTrue();
});

test('course teacher can transfer own course result info', function () {
  $targetAssessments = Assessment::getAssessments(
    TermType::Second->value,
    true,
    $this->classification->id
  );
  $assessmentMap = $targetAssessments
    ->mapWithKeys(fn($assessment) => [(string) $assessment->id => []])
    ->toArray();
  $assessmentMap[(string) $this->targetAssessment->id] = [
    $this->sourceAssessment->id
  ];
  $assessmentMap['exam'] = ['exam'];

  $response = actingAs($this->teacher)->post(
    route('institutions.course-result-info.transfer.store', [
      $this->institution,
      $this->courseResultInfo
    ]),
    [
      'term' => TermType::Second->value,
      'for_mid_term' => true,
      'assessment_map' => $assessmentMap
    ]
  );

  $response->assertOk();
});

test('teacher cannot transfer another teachers course result info', function () {
  $otherTeacher = User::factory()->teacher($this->institution)->create();
  $targetAssessments = Assessment::getAssessments(
    TermType::Second->value,
    true,
    $this->classification->id
  );
  $assessmentMap = $targetAssessments
    ->mapWithKeys(fn($assessment) => [(string) $assessment->id => []])
    ->toArray();
  $assessmentMap[(string) $this->targetAssessment->id] = [
    $this->sourceAssessment->id
  ];
  $assessmentMap['exam'] = ['exam'];

  $response = actingAs($otherTeacher)->post(
    route('institutions.course-result-info.transfer.store', [
      $this->institution,
      $this->courseResultInfo
    ]),
    [
      'term' => TermType::Second->value,
      'for_mid_term' => true,
      'assessment_map' => $assessmentMap
    ]
  );

  $response->assertForbidden();
});
