<?php

use App\Actions\CourseResult\ClassResultInfoAction;
use App\Actions\CourseResult\GetGrade;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\ClassGroupResultInfo;
use App\Models\ClassResultInfo;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->session = AcademicSession::factory()->create();
  $this->group = ClassificationGroup::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classA = Classification::factory()
    ->classificationGroup($this->group)
    ->create();
  $this->classB = Classification::factory()
    ->classificationGroup($this->group)
    ->create();
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->teacher = User::factory()
    ->teacher($this->institution)
    ->create();
});

it(
  'adds class group positions and records when term results are generated',
  function () {
    $studentsA = Student::factory()
      ->count(2)
      ->withInstitution($this->institution, $this->classA)
      ->create();
    $studentsB = Student::factory()
      ->count(2)
      ->withInstitution($this->institution, $this->classB)
      ->create();

    $resultsA = [90, 80];
    $resultsB = [70, 60];

    foreach ($studentsA as $index => $student) {
      $score = $resultsA[$index];
      CourseResult::factory()->create([
        'institution_id' => $this->institution->id,
        'student_id' => $student->id,
        'teacher_user_id' => $this->teacher->id,
        'course_id' => $this->course->id,
        'classification_id' => $this->classA->id,
        'academic_session_id' => $this->session->id,
        'term' => TermType::First,
        'for_mid_term' => false,
        'exam' => $score - 40,
        'result' => $score,
        'grade' => GetGrade::run($score)
      ]);
    }

    foreach ($studentsB as $index => $student) {
      $score = $resultsB[$index];
      CourseResult::factory()->create([
        'institution_id' => $this->institution->id,
        'student_id' => $student->id,
        'teacher_user_id' => $this->teacher->id,
        'course_id' => $this->course->id,
        'classification_id' => $this->classB->id,
        'academic_session_id' => $this->session->id,
        'term' => TermType::First,
        'for_mid_term' => false,
        'exam' => $score - 40,
        'result' => $score,
        'grade' => GetGrade::run($score)
      ]);
    }

    ClassResultInfoAction::make()->calculate(
      $this->classA,
      $this->session->id,
      TermType::First,
      false
    );

    ClassResultInfoAction::make()->calculate(
      $this->classB,
      $this->session->id,
      TermType::First,
      false
    );

    $termResults = TermResult::query()
      ->where('institution_id', $this->institution->id)
      ->where('academic_session_id', $this->session->id)
      ->where('term', TermType::First)
      ->where('for_mid_term', false)
      ->orderByDesc('average')
      ->get();

    expect($termResults)->toHaveCount(4);
    expect($termResults->pluck('class_group_position')->all())->toBe([
      1,
      2,
      3,
      4
    ]);

    $classResultInfos = ClassResultInfo::query()
      ->where('institution_id', $this->institution->id)
      ->where('academic_session_id', $this->session->id)
      ->where('term', TermType::First)
      ->where('for_mid_term', false)
      ->whereIn('classification_id', [$this->classA->id, $this->classB->id])
      ->get();

    $expectedNumStudents = $classResultInfos->sum('num_of_students');
    $expectedTotalScore = $classResultInfos->sum('total_score');

    $classGroupInfo = ClassGroupResultInfo::query()
      ->where('institution_id', $this->institution->id)
      ->where('academic_session_id', $this->session->id)
      ->where('classification_group_id', $this->group->id)
      ->where('term', TermType::First)
      ->where('for_mid_term', false)
      ->first();

    expect($classGroupInfo)->not->toBeNull();
    expect($classGroupInfo->num_of_students)->toBe($expectedNumStudents);
    expect($classGroupInfo->total_score)->toBe($expectedTotalScore);
    expect($classGroupInfo->average)->toBe(
      round($expectedTotalScore / $expectedNumStudents, 2)
    );
    expect($classGroupInfo->min_score)->toBe(
      $classResultInfos->min('min_score')
    );
    expect($classGroupInfo->max_score)->toBe(
      $classResultInfos->max('max_score')
    );
  }
);
