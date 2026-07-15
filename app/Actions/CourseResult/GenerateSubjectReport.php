<?php

namespace App\Actions\CourseResult;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\CourseTeacher;
use Illuminate\Support\Collection;

class GenerateSubjectReport
{
  public const ALL_TERMS = 'all-terms';

  public function __construct(
    private Classification $classification,
    private AcademicSession $academicSession,
    private string $term,
    private bool $forMidTerm = false
  ) {
  }

  public static function run(
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm = false
  ): array {
    return (new self(
      $classification,
      $academicSession,
      $term,
      $forMidTerm
    ))->getReportRows();
  }

  public static function runSections(
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm = false
  ): array {
    if ($term !== self::ALL_TERMS) {
      return [
        [
          'key' => $term,
          'title' => self::termTitle($term),
          'subjectReport' => self::run(
            $classification,
            $academicSession,
            $term,
            $forMidTerm
          )
        ]
      ];
    }

    $sections = [
      [
        'key' => self::ALL_TERMS,
        'title' => 'Cumulative',
        'subjectReport' => self::runCumulative(
          $classification,
          $academicSession,
          $forMidTerm
        )
      ]
    ];

    foreach (TermType::cases() as $termType) {
      $sections[] = [
        'key' => $termType->value,
        'title' => self::termTitle($termType->value),
        'subjectReport' => self::run(
          $classification,
          $academicSession,
          $termType->value,
          $forMidTerm
        )
      ];
    }

    return $sections;
  }

  public static function runCumulative(
    Classification $classification,
    AcademicSession $academicSession,
    bool $forMidTerm = false
  ): array {
    return (new SubjectReportCumulativeSummary(
      $classification,
      $academicSession,
      $forMidTerm
    ))->getReportRows();
  }

  public function getReportRows(): array
  {
    $courseResultInfo = CourseResultInfo::query()
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', $this->term)
      ->where('for_mid_term', $this->forMidTerm)
      ->with('course')
      ->get()
      ->keyBy('course_id');

    $courseResults = CourseResult::query()
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', $this->term)
      ->where('for_mid_term', $this->forMidTerm)
      ->with('student.user', 'course')
      ->get()
      ->groupBy('course_id');

    $courseTeachers = CourseTeacher::query()
      ->where('classification_id', $this->classification->id)
      ->with('user')
      ->get()
      ->groupBy('course_id');

    // $gradeKeys = collect(Grade::cases())
    //   ->map(fn($grade) => $grade->value)
    //   ->all();

    $reportRows = [];
    foreach ($courseResultInfo as $courseId => $info) {
      $results = $courseResults->get($courseId, collect());
      $highestScore = null;
      $lowestScore = null;
      $highestStudent = null;
      $lowestStudent = null;
      // $passCount = 0;
      // $failCount = 0;
      // $gradeBreakdown = array_fill_keys($gradeKeys, 0);

      foreach ($results as $courseResult) {
        $score = $courseResult->result;
        // $grade =
        //   $courseResult->grade ??
        //   GetGrade::run($score, $this->classification, $this->forMidTerm);
        // if (!isset($gradeBreakdown[$grade])) {
        //   $gradeBreakdown[$grade] = 0;
        // }
        // $gradeBreakdown[$grade]++;

        // if ($grade === Grade::F->value) {
        //   $failCount++;
        // } else {
        //   $passCount++;
        // }

        if ($highestScore === null || $score > $highestScore) {
          $highestScore = $score;
          $highestStudent = $courseResult->student?->user?->full_name;
        }
        if ($lowestScore === null || $score < $lowestScore) {
          $lowestScore = $score;
          $lowestStudent = $courseResult->student?->user?->full_name;
        }
      }

      $teacherNames = $courseTeachers
        ->get($courseId, collect())
        ->map(fn($courseTeacher) => $courseTeacher->user?->full_name)
        ->filter()
        ->values()
        ->all();

      $reportRows[] = [
        'course' => $info->course,
        'course_id' => $courseId,
        'num_of_students' => $info->num_of_students,
        'total_score' => $info->total_score,
        'max_obtainable_score' => $info->max_obtainable_score,
        'max_score' => $info->max_score,
        'min_score' => $info->min_score,
        'average' => $info->average,
        // 'pass_count' => $passCount,
        // 'fail_count' => $failCount,
        'highest_score' => $highestScore,
        'highest_student' => $highestStudent,
        'lowest_score' => $lowestScore,
        'lowest_student' => $lowestStudent,
        // 'grade_breakdown' => $gradeBreakdown,
        'teachers' => $teacherNames
      ];
    }

    return $reportRows;
  }

  private static function termTitle(string $term): string
  {
    return ucfirst($term) . ' Term';
  }
}

class SubjectReportCumulativeSummary
{
  public function __construct(
    private Classification $classification,
    private AcademicSession $academicSession,
    private bool $forMidTerm
  ) {
  }

  public function getReportRows(): array
  {
    $terms = array_map(fn(TermType $term) => $term->value, TermType::cases());
    $courseResultInfo = CourseResultInfo::query()
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->whereIn('term', $terms)
      ->where('for_mid_term', $this->forMidTerm)
      ->with('course')
      ->get()
      ->groupBy('course_id');

    $courseResultsByCourse = CourseResult::query()
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->whereIn('term', $terms)
      ->where('for_mid_term', $this->forMidTerm)
      ->with('student.user', 'course')
      ->get()
      ->groupBy('course_id');

    $courseTeachers = CourseTeacher::query()
      ->where('classification_id', $this->classification->id)
      ->with('user')
      ->get()
      ->groupBy('course_id');

    return $courseResultInfo
      ->map(
        fn(Collection $infos, int $courseId) => $this->buildRow(
          $courseId,
          $infos,
          $courseResultsByCourse->get($courseId, collect()),
          $courseTeachers->get($courseId, collect())
        )
      )
      ->values()
      ->all();
  }

  private function buildRow(
    int $courseId,
    Collection $infos,
    Collection $courseResults,
    Collection $courseTeachers
  ): array {
    $studentScores = $courseResults
      ->groupBy('student_id')
      ->map(fn(Collection $results) => $results->sum('result'));

    [
      $highestScore,
      $highestStudent,
      $lowestScore,
      $lowestStudent
    ] = $this->getScoreExtremes($courseResults, $studentScores);

    $numOfStudents = $studentScores->count();
    $totalScore = $studentScores->sum();

    return [
      'course' => $infos->first()->course ?? Course::query()->find($courseId),
      'course_id' => $courseId,
      'num_of_students' => $numOfStudents,
      'total_score' => $totalScore,
      'max_obtainable_score' => $infos->sum('max_obtainable_score'),
      'max_score' => $studentScores->isEmpty() ? 0 : $studentScores->max(),
      'min_score' => $studentScores->isEmpty() ? 0 : $studentScores->min(),
      'average' =>
        $numOfStudents < 1 ? 0 : round($totalScore / $numOfStudents, 2),
      'highest_score' => $highestScore,
      'highest_student' => $highestStudent,
      'lowest_score' => $lowestScore,
      'lowest_student' => $lowestStudent,
      'teachers' => $this->teacherNames($courseTeachers)
    ];
  }

  private function getScoreExtremes(
    Collection $courseResults,
    Collection $studentScores
  ): array {
    $highestStudentId = $studentScores
      ->sortDesc()
      ->keys()
      ->first();
    $lowestStudentId = $studentScores
      ->sort()
      ->keys()
      ->first();
    $highestStudent = $courseResults->firstWhere(
      'student_id',
      $highestStudentId
    )?->student?->user?->full_name;
    $lowestStudent = $courseResults->firstWhere('student_id', $lowestStudentId)
      ?->student?->user?->full_name;

    return [
      $highestStudentId === null
        ? null
        : $studentScores->get($highestStudentId),
      $highestStudent,
      $lowestStudentId === null ? null : $studentScores->get($lowestStudentId),
      $lowestStudent
    ];
  }

  private function teacherNames(Collection $courseTeachers): array
  {
    return $courseTeachers
      ->map(fn($courseTeacher) => $courseTeacher->user?->full_name)
      ->filter()
      ->values()
      ->all();
  }
}
