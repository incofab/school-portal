<?php

namespace App\Actions\CourseResult;

use App\Enums\Grade;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\CourseTeacher;

class GenerateSubjectReport
{
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
}
