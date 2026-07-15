<?php

namespace App\Actions\CourseResult;

use App\Actions\ResultUtil;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Student;
use Illuminate\Support\Collection;

class GenerateSingleSubjectReport
{
  public function __construct(
    private Classification $classification,
    private AcademicSession $academicSession,
    private Course $course,
    private bool $forMidTerm = false
  ) {
  }

  public static function run(
    Classification $classification,
    AcademicSession $academicSession,
    Course $course,
    bool $forMidTerm = false
  ): array {
    return (new self(
      $classification,
      $academicSession,
      $course,
      $forMidTerm
    ))->getReportRows();
  }

  public function getReportRows(): array
  {
    $terms = array_map(fn(TermType $term) => $term->value, TermType::cases());
    $students = Student::query()
      ->joinInstitution($this->classification->institution_id)
      ->where('students.classification_id', $this->classification->id)
      ->select('students.*')
      ->with('user')
      ->get()
      ->sortBy(fn(Student $student) => $student->user?->full_name)
      ->values();

    $courseResultsByTerm = CourseResult::query()
      ->where('institution_id', $this->classification->institution_id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('course_id', $this->course->id)
      ->whereIn('term', $terms)
      ->where('for_mid_term', $this->forMidTerm)
      ->get()
      ->groupBy(fn(CourseResult $courseResult) => $courseResult->term->value);

    $termPositions = $this->termPositions($courseResultsByTerm);
    $overallScores = $this->overallScores($students, $courseResultsByTerm);
    $overallPositions = $this->positions($overallScores);

    return $students
      ->map(
        fn(Student $student) => $this->studentRow(
          $student,
          $terms,
          $courseResultsByTerm,
          $termPositions,
          $overallScores,
          $overallPositions
        )
      )
      ->values()
      ->all();
  }

  private function studentRow(
    Student $student,
    array $terms,
    Collection $courseResultsByTerm,
    array $termPositions,
    array $overallScores,
    array $overallPositions
  ): array {
    $termResults = [];

    foreach ($terms as $term) {
      $courseResult = $courseResultsByTerm
        ->get($term, collect())
        ->firstWhere('student_id', $student->id);

      $termResults[$term] = [
        'score' => $courseResult?->result,
        'position' => $termPositions[$term][$student->id] ?? null
      ];
    }

    return [
      'student' => $student,
      'student_id' => $student->id,
      'term_results' => $termResults,
      'overall' => [
        'score' => $overallScores[$student->id] ?? null,
        'position' => $overallPositions[$student->id] ?? null
      ]
    ];
  }

  private function termPositions(Collection $courseResultsByTerm): array
  {
    return $courseResultsByTerm
      ->map(fn(Collection $courseResults) => $this->positions(
        $courseResults
          ->mapWithKeys(
            fn(CourseResult $courseResult) => [
              $courseResult->student_id => $courseResult->result
            ]
          )
          ->all()
      ))
      ->all();
  }

  private function overallScores(
    Collection $students,
    Collection $courseResultsByTerm
  ): array {
    return $students
      ->mapWithKeys(function (Student $student) use ($courseResultsByTerm) {
        $scores = $courseResultsByTerm
          ->map(
            fn(Collection $courseResults) => $courseResults->firstWhere(
              'student_id',
              $student->id
            )?->result
          )
          ->filter(fn($score) => $score !== null);

        if ($scores->isEmpty()) {
          return [];
        }

        return [$student->id => round($scores->sum() / $scores->count(), 2)];
      })
      ->all();
  }

  private function positions(array $scores): array
  {
    return collect(ResultUtil::assignPositions($scores))
      ->mapWithKeys(fn($assignedPosition) => [
        $assignedPosition->getId() => $assignedPosition->getPosition()
      ])
      ->all();
  }
}
