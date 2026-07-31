<?php

namespace App\Actions\CourseResult;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Student;
use App\Models\TermResult;
use Illuminate\Support\Collection;

class GenerateFullClassReport
{
  public function __construct(
    private Classification $classification,
    private AcademicSession $academicSession,
    private TermType $term
  ) {
  }

  public static function run(
    Classification $classification,
    AcademicSession $academicSession,
    TermType $term
  ): array {
    return (new self($classification, $academicSession, $term))->getReport();
  }

  public static function empty(): array
  {
    return [
      'courses' => [],
      'students' => []
    ];
  }

  public function getReport(): array
  {
    $students = $this->students();
    $courseResults = $this->courseResults();
    $courses = $this->courses($courseResults);
    $assessments = Assessment::getAssessments(
      $this->term,
      false,
      $this->classification,
      true
    );
    $termResults = $this->termResults();

    return [
      'courses' => $courses
        ->map(
          fn(Course $course) => [
            ...$course->toArray(),
            'assessments' => $assessments->values()->all()
          ]
        )
        ->values()
        ->all(),
      'students' => $students
        ->map(
          fn(Student $student) => $this->studentRow(
            $student,
            $courses,
            $assessments,
            $courseResults,
            $termResults
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

  private function courseResults(): Collection
  {
    return CourseResult::query()
      ->where('institution_id', $this->classification->institution_id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', $this->term)
      ->where('for_mid_term', false)
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
      ->orderedByCourseOrder()
      ->orderBy('title')
      ->get();
  }

  private function termResults(): Collection
  {
    return TermResult::query()
      ->where('institution_id', $this->classification->institution_id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', $this->term)
      ->where('for_mid_term', false)
      ->get()
      ->keyBy('student_id');
  }

  private function studentRow(
    Student $student,
    Collection $courses,
    Collection $assessments,
    Collection $courseResults,
    Collection $termResults
  ): array {
    $subjectResults = [];
    $subjectTotals = collect();

    foreach ($courses as $course) {
      $courseResult = $courseResults
        ->where('student_id', $student->id)
        ->firstWhere('course_id', $course->id);

      $subjectResults[$course->id] = $this->subjectResult(
        $courseResult,
        $assessments
      );

      if ($courseResult?->result !== null) {
        $subjectTotals->push($courseResult->result);
      }
    }

    $termResult = $termResults->get($student->id);

    return [
      'student' => $student,
      'student_id' => $student->id,
      'subject_results' => $subjectResults,
      'overall_total_score' => $subjectTotals->isEmpty()
        ? null
        : $subjectTotals->sum(),
      'average' => $termResult?->average,
      'position' => $termResult?->position
    ];
  }

  private function subjectResult(
    ?CourseResult $courseResult,
    Collection $assessments
  ): array {
    return [
      'assessments' => $assessments
        ->mapWithKeys(
          fn(Assessment $assessment) => [
            $assessment->assessmentResultKey() => $courseResult
              ? Assessment::assessmentScoreFromValues(
                $courseResult->assessment_values,
                $assessment,
                null
              )
              : null
          ]
        )
        ->all(),
      'exam' => $courseResult?->exam,
      'total' => $courseResult?->result
    ];
  }
}
