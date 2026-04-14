<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\ClassResultInfo;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  ClassResultInfo::clearResultLockCache();
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->teacher = User::factory()
    ->teacher($this->institution)
    ->create();
  [$this->classA, $this->classB] = Classification::factory(2)
    ->withInstitution($this->institution)
    ->create();

  $this->requestData = [
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'for_mid_term' => false,
    'force_calculate_term_result' => false
  ];

  $this->routeName = 'institutions.class-result-info.calculate';
});

function seedClassResultCalculationData(
  Institution $institution,
  Classification $classification,
  AcademicSession $academicSession,
  Course $course,
  User $teacher
) {
  $student = Student::factory()
    ->withInstitution($institution, $classification)
    ->create();

  CourseResult::factory()->create([
    'institution_id' => $institution->id,
    'student_id' => $student->id,
    'teacher_user_id' => $teacher->id,
    'course_id' => $course->id,
    'classification_id' => $classification->id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::First,
    'for_mid_term' => false,
    'exam' => 60,
    'result' => 60,
    'grade' => 'B'
  ]);
}

it('calculates result info for a route class', function () {
  seedClassResultCalculationData(
    $this->institution,
    $this->classA,
    $this->academicSession,
    $this->course,
    $this->teacher
  );

  actingAs($this->instAdmin)
    ->postJson(route($this->routeName, [$this->institution->uuid]), [
      ...$this->requestData,
      'classifications' => [$this->classA->id]
    ])
    ->assertOk();

  expect(
    $classResultInfo = ClassResultInfo::query()
      ->where('institution_id', $this->institution->id)
      ->where('classification_id', $this->classA->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', TermType::First)
      ->where('for_mid_term', false)
      ->first()
  )->not->toBeNull();

  expect($classResultInfo->is_locked)->toBeTrue();
});

it('can unlock and relock a class result info record', function () {
  $classResultInfo = ClassResultInfo::factory()
    ->classification($this->classA)
    ->create([
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First,
      'for_mid_term' => false,
      'is_locked' => true
    ]);

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.class-result-info.lock', [
        $this->institution->uuid,
        $classResultInfo
      ]),
      ['is_locked' => false]
    )
    ->assertOk();

  expect($classResultInfo->fresh()->is_locked)->toBeFalse();

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.class-result-info.lock', [
        $this->institution->uuid,
        $classResultInfo
      ]),
      ['is_locked' => true]
    )
    ->assertOk();

  expect($classResultInfo->fresh()->is_locked)->toBeTrue();
});

it('allows the form teacher to update the lock', function () {
  $this->classA->fill(['form_teacher_id' => $this->teacher->id])->save();
  $classResultInfo = ClassResultInfo::factory()
    ->classification($this->classA)
    ->create([
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First,
      'for_mid_term' => false,
      'is_locked' => true
    ]);

  actingAs($this->teacher)
    ->postJson(
      route('institutions.class-result-info.lock', [
        $this->institution->uuid,
        $classResultInfo
      ]),
      ['is_locked' => false]
    )
    ->assertOk();

  expect($classResultInfo->fresh()->is_locked)->toBeFalse();
});

it('prevents a teacher who is not the form teacher from updating the lock', function () {
  $otherTeacher = User::factory()
    ->teacher($this->institution)
    ->create();
  $classResultInfo = ClassResultInfo::factory()
    ->classification($this->classA)
    ->create([
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First,
      'for_mid_term' => false,
      'is_locked' => true
    ]);

  actingAs($otherTeacher)
    ->postJson(
      route('institutions.class-result-info.lock', [
        $this->institution->uuid,
        $classResultInfo
      ]),
      ['is_locked' => false]
    )
    ->assertForbidden();

  expect($classResultInfo->fresh()->is_locked)->toBeTrue();
});

it('locks the result info again when recalculated', function () {
  seedClassResultCalculationData(
    $this->institution,
    $this->classA,
    $this->academicSession,
    $this->course,
    $this->teacher
  );

  $classResultInfo = ClassResultInfo::factory()
    ->classification($this->classA)
    ->create([
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First,
      'for_mid_term' => false,
      'is_locked' => false
    ]);

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.class-result-info.recalculate', [
        $this->institution->uuid,
        $classResultInfo
      ])
    )
    ->assertOk();

  expect($classResultInfo->fresh()->is_locked)->toBeTrue();
});

it('prevents duplicate lock lookups while recording multiple results', function () {
  seedClassResultCalculationData(
    $this->institution,
    $this->classA,
    $this->academicSession,
    $this->course,
    $this->teacher
  );

  $classResultInfo = ClassResultInfo::factory()
    ->classification($this->classA)
    ->create([
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First,
      'for_mid_term' => false,
      'is_locked' => false
    ]);

  ClassResultInfo::ensureResultIsUnlocked(
    $this->classA->id,
    $this->academicSession->id,
    TermType::First,
    false
  );

  $classResultInfo->fill(['is_locked' => true])->save();

  expect(
    fn() => ClassResultInfo::ensureResultIsUnlocked(
      $this->classA->id,
      $this->academicSession->id,
      TermType::First,
      false
    )
  )->not->toThrow(HttpException::class);

  ClassResultInfo::clearResultLockCache();

  expect(
    fn() => ClassResultInfo::ensureResultIsUnlocked(
      $this->classA->id,
      $this->academicSession->id,
      TermType::First,
      false
    )
  )->toThrow(HttpException::class);
});

it(
  'calculates result info for multiple classes supplied in the request',
  function () {
    seedClassResultCalculationData(
      $this->institution,
      $this->classA,
      $this->academicSession,
      $this->course,
      $this->teacher
    );
    seedClassResultCalculationData(
      $this->institution,
      $this->classB,
      $this->academicSession,
      $this->course,
      $this->teacher
    );

    actingAs($this->instAdmin)
      ->postJson(route($this->routeName, [$this->institution->uuid]), [
        ...$this->requestData,
        'classifications' => [$this->classA->id, $this->classB->id]
      ])
      ->assertOk();

    expect(
      ClassResultInfo::query()
        ->where('institution_id', $this->institution->id)
        ->where('academic_session_id', $this->academicSession->id)
        ->where('term', TermType::First)
        ->where('for_mid_term', false)
        ->whereIn('classification_id', [$this->classA->id, $this->classB->id])
        ->count()
    )->toBe(2);
  }
);
