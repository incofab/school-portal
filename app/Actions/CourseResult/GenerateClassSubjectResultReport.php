<?php

namespace App\Actions\CourseResult;

use App\Actions\ResultUtil;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Student;
use Illuminate\Support\Collection;

class GenerateClassSubjectResultReport
{
  public function __construct(
    private Classification $classification,
    private AcademicSession $academicSession,
    private bool $forMidTerm = false
  ) {
  }

  public static function run(
    Classification $classification,
    AcademicSession $academicSession,
    bool $forMidTerm = false
  ): array {
    return (new self(
      $classification,
      $academicSession,
      $forMidTerm
    ))->getReport();
  }

  public function getReport(): array
  {
    $terms = array_map(fn(TermType $term) => $term->value, TermType::cases());
    $students = $this->students();
    $courseResults = $this->courseResults($terms);
    $courses = $this->courses($courseResults);
    $subjectPositions = $this->subjectPositions(
      $students,
      $courses,
      $terms,
      $courseResults
    );

    return [
      'courses' => $courses->values()->all(),
      'students' => $students
        ->map(
          fn(Student $student) => $this->studentRow(
            $student,
            $courses,
            $terms,
            $courseResults,
            $subjectPositions
          )
        )
        ->values()
        ->all()
    ];
  }

  private function students(): Collection
  {
    return Student::query()
      ->joinInstitution($this->classification->institution_id)
      ->where('students.classification_id', $this->classification->id)
      ->select('students.*')
      ->with('user')
      ->get()
      ->sortBy(fn(Student $student) => $student->user?->full_name)
      ->values();
  }

  private function courseResults(array $terms): Collection
  {
    return CourseResult::query()
      ->where('institution_id', $this->classification->institution_id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->whereIn('term', $terms)
      ->where('for_mid_term', $this->forMidTerm)
      ->with('course')
      ->get();
  }

  private function courses(Collection $courseResults): Collection
  {
    $mappedCourseIds = CourseTeacher::query()
      ->where('institution_id', $this->classification->institution_id)
      ->where('classification_id', $this->classification->id)
      ->pluck('course_id');

    $resultCourseIds = $courseResults->pluck('course_id');

    return Course::query()
      ->where('institution_id', $this->classification->institution_id)
      ->whereIn('id', $mappedCourseIds->merge($resultCourseIds)->unique())
      ->orderBy('order')
      ->orderBy('title')
      ->get();
  }

  private function studentRow(
    Student $student,
    Collection $courses,
    array $terms,
    Collection $courseResults,
    array $subjectPositions
  ): array {
    $subjectResults = [];

    foreach ($courses as $course) {
      $scores = $this->scores($student, $course, $terms, $courseResults);
      $filledScores = collect($scores)->filter(fn($score) => $score !== null);

      $subjectResults[$course->id] = [
        'terms' => $scores,
        'total' => $filledScores->isEmpty() ? null : $filledScores->sum(),
        'average' => $filledScores->isEmpty()
          ? null
          : round($filledScores->sum() / $filledScores->count(), 2),
        'position' => $subjectPositions[$course->id][$student->id] ?? null
      ];
    }

    return [
      'student' => $student,
      'student_id' => $student->id,
      'subject_results' => $subjectResults
    ];
  }

  private function scores(
    Student $student,
    Course $course,
    array $terms,
    Collection $courseResults
  ): array {
    $scores = [];

    foreach ($terms as $term) {
      $scores[$term] = $courseResults
        ->where('student_id', $student->id)
        ->where('course_id', $course->id)
        ->firstWhere('term.value', $term)?->result;
    }

    return $scores;
  }

  private function subjectPositions(
    Collection $students,
    Collection $courses,
    array $terms,
    Collection $courseResults
  ): array {
    return $courses
      ->mapWithKeys(
        fn(Course $course) => [
          $course->id => $this->positions(
            $students,
            $course,
            $terms,
            $courseResults
          )
        ]
      )
      ->all();
  }

  private function positions(
    Collection $students,
    Course $course,
    array $terms,
    Collection $courseResults
  ): array {
    $averages = $students
      ->mapWithKeys(function (Student $student) use (
        $course,
        $terms,
        $courseResults
      ) {
        $scores = collect(
          $this->scores($student, $course, $terms, $courseResults)
        )->filter(fn($score) => $score !== null);

        if ($scores->isEmpty()) {
          return [];
        }

        return [$student->id => round($scores->sum() / $scores->count(), 2)];
      })
      ->all();

    return collect(ResultUtil::assignPositions($averages))
      ->mapWithKeys(
        fn($assignedPosition) => [
          $assignedPosition->getId() => $assignedPosition->getPosition()
        ]
      )
      ->all();
  }
}
