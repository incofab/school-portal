<?php

namespace App\Actions\CourseResult;

use App\Actions\ResultUtil;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermResult;
use Illuminate\Support\Collection;

class FormatCummulativeResult
{
  private Institution $institution;

  public function __construct(
    private AcademicSession $academicSession,
    private Classification $classification,
    private ?string $term
  ) {
    $this->institution = currentInstitution();
  }

  public static function run(
    AcademicSession $academicSession,
    Classification $classification,
    ?string $term
  ): array {
    return (new self($academicSession, $classification, $term))->execute();
  }

  public static function empty(): array
  {
    return [
      'terms' => [],
      'coursesByTerm' => [
        TermType::First->value => [],
        TermType::Second->value => [],
        TermType::Third->value => []
      ],
      'sessionResults' => [],
      'courses' => [
        'firstTermCourses' => [],
        'secondTermCourses' => [],
        'thirdTermCourses' => []
      ]
    ];
  }

  public function execute(): array
  {
    $terms = $this->selectedTerms();
    $coursesByTerm = $this->getCoursesByTerm($terms);

    $sessionResults = $this->institution
      ->sessionResults()
      ->where('academic_session_id', $this->academicSession->id)
      ->where('classification_id', $this->classification->id)
      ->with('student.user')
      ->with([
        'student.termResults' => fn($query) => $query
          ->where('academic_session_id', $this->academicSession->id)
          ->where('classification_id', $this->classification->id)
          ->where('for_mid_term', false)
          ->whereIn('term', $terms),
        'student.courseResults' => fn($query) => $query
          ->where('academic_session_id', $this->academicSession->id)
          ->where('classification_id', $this->classification->id)
          ->where('for_mid_term', false)
          ->whereIn('term', $terms)
      ])
      ->orderBy('id')
      ->get();

    $formattedSessionResults = $sessionResults
      ->filter(fn(SessionResult $sessionResult) => $sessionResult->student)
      ->map(
        fn(SessionResult $sessionResult) => StudentSessionResultFormat::run(
          $sessionResult,
          $terms
        )
      )
      ->values();

    $formattedSessionResults = CummulativeResultSummary::apply(
      $formattedSessionResults
    );

    return [
      'terms' => $terms,
      'coursesByTerm' => $coursesByTerm,
      'sessionResults' => $formattedSessionResults->all(),
      'courses' => [
        'firstTermCourses' => $coursesByTerm[TermType::First->value] ?? [],
        'secondTermCourses' => $coursesByTerm[TermType::Second->value] ?? [],
        'thirdTermCourses' => $coursesByTerm[TermType::Third->value] ?? []
      ]
    ];
  }

  private function selectedTerms(): array
  {
    if ($this->term) {
      return [$this->term];
    }

    return array_map(fn(TermType $term) => $term->value, TermType::cases());
  }

  private function getCoursesByTerm(array $terms): array
  {
    $courses = Course::query()
      ->select('courses.*', 'course_results.term as result_term')
      ->join('course_results', 'courses.id', 'course_results.course_id')
      ->where('course_results.academic_session_id', $this->academicSession->id)
      ->where('course_results.classification_id', $this->classification->id)
      ->where('course_results.for_mid_term', false)
      ->whereIn('course_results.term', $terms)
      ->orderedByCourseOrder()
      ->orderBy('courses.title')
      ->get()
      ->groupBy('result_term');

    return collect($terms)
      ->mapWithKeys(
        fn(string $term) => [
          $term => ($courses[$term] ?? collect())
            ->unique('id')
            ->values()
            ->all()
        ]
      )
      ->all();
  }
}

class CummulativeResultSummary
{
  /**
   * @param Collection<int, array{
   *   student: Student,
   *   termResults: array<string, TermResult|null>,
   *   courseResults: array<string, array<int, CourseResult>>
   * }> $sessionResults
   */
  public static function apply(Collection $sessionResults): Collection
  {
    $averagesByStudent = self::averagesByStudent($sessionResults);
    $positionsByStudent = collect(
      ResultUtil::assignPositions($averagesByStudent)
    )->mapWithKeys(
      fn($assignedPosition) => [
        $assignedPosition->getId() => $assignedPosition->getPosition()
      ]
    );

    return $sessionResults->map(function (array $sessionResult) use (
      $averagesByStudent,
      $positionsByStudent
    ) {
      $studentId = $sessionResult['student']->id;

      return [
        ...$sessionResult,
        'summary' => [
          'average' => $averagesByStudent[$studentId] ?? null,
          'position' => $positionsByStudent[$studentId] ?? null
        ]
      ];
    });
  }

  private static function averagesByStudent(Collection $sessionResults): array
  {
    return $sessionResults
      ->mapWithKeys(function (array $sessionResult) {
        $termAverages = collect($sessionResult['termResults'])
          ->filter(
            fn(?TermResult $termResult) => $termResult?->average !== null
          )
          ->map(fn(TermResult $termResult) => $termResult->average)
          ->values();

        if ($termAverages->isEmpty()) {
          return [];
        }

        return [
          $sessionResult['student']->id =>
            $termAverages->sum() / $termAverages->count()
        ];
      })
      ->all();
  }
}

class StudentSessionResultFormat
{
  private Student $student;

  public function __construct(
    SessionResult $sessionResult,
    private array $terms
  ) {
    $this->student = $sessionResult->student;
  }

  public static function run(SessionResult $sessionResult, array $terms): array
  {
    return (new self($sessionResult, $terms))->format();
  }

  public function format(): array
  {
    return [
      'student' => $this->student,
      'termResults' => $this->termResultsByTerm(),
      'courseResults' => $this->courseResultsByTerm()
    ];
  }

  private function termResultsByTerm(): array
  {
    $termResults = $this->student->termResults->keyBy(
      fn(TermResult $termResult) => $termResult->term->value
    );

    return collect($this->terms)
      ->mapWithKeys(fn(string $term) => [$term => $termResults->get($term)])
      ->all();
  }

  private function courseResultsByTerm(): array
  {
    $courseResults = $this->student->courseResults->groupBy(
      fn(CourseResult $courseResult) => $courseResult->term->value
    );

    return collect($this->terms)
      ->mapWithKeys(
        fn(string $term) => [
          $term => $this->courseResultsByCourse(
            $courseResults->get($term, collect())
          )
        ]
      )
      ->all();
  }

  /**
   * @param Collection<int, CourseResult> $courseResults
   */
  private function courseResultsByCourse(Collection $courseResults): array
  {
    return $courseResults->keyBy('course_id')->all();
  }
}
