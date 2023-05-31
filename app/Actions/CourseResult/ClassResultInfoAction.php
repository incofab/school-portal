<?php
namespace App\Actions\CourseResult;

use App\Enums\TermType;
use App\Models\Classification;
use App\Models\CourseResult;
use App\Models\ClassResultInfo;
use Illuminate\Database\Eloquent\Collection;

class ClassResultInfoAction
{
  // function __construct(private CourseResult $courseResult)
  // {
  // }

  public static function make()
  {
    return new self();
  }

  // public function firstOrCreate(): ClassResultInfo
  // {
  //   $classResultInfo = ClassResultInfo::query()
  //     // ->where('course_id', $this->courseResult->course_id)
  //     ->where('classification_id', $this->courseResult->classification_id)
  //     ->where('academic_session_id', $this->courseResult->academic_session_id)
  //     ->where('term', $this->courseResult->term)
  //     ->first();

  //   $classResultInfo = !empty($classResultInfo)
  //     ? $classResultInfo
  //     : $this->recalculate();

  //   return $classResultInfo;
  // }

  public function recalculate(ClassResultInfo $classResultInfo): ClassResultInfo
  {
    return $this->calculate(
      $classResultInfo->classification,
      $classResultInfo->academic_session_id,
      $classResultInfo->term
    );
  }

  public function calculate(
    Classification $classification,
    int $academicSessionId,
    string|TermType $term
  ): ClassResultInfo {
    $queryCourseResults = CourseResult::query()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSessionId)
      ->where('term', $term);

    $courseResults = $queryCourseResults->get();
    $courseResultsGroupedByCourses = $queryCourseResults
      ->select('course_id')
      ->groupBy('course_id')
      ->get();
    $classResultsGroupedByStudents = $queryCourseResults
      ->select('student_id')
      ->groupBy('student_id')
      ->get();

    $numOfCourses = $courseResultsGroupedByCourses->count();
    $numOfStudents = $classResultsGroupedByStudents->count();

    [$totalScore, $minScore, $maxScore] = $this->getTotalScore($courseResults);

    $bindingData = [
      'institution_id' => $classification->institution_id,
      'term' => $term,
      'academic_session_id' => $academicSessionId,
      // 'course_id' => $this->courseResult->id,
      'classification_id' => $classification->id
    ];

    $data = [
      'num_of_courses' => $numOfCourses,
      'num_of_students' => $numOfStudents,
      'total_score' => $totalScore,
      'max_obtainable_score' => $numOfCourses * $numOfStudents * 100,
      'average' => round($totalScore / $numOfCourses, 2),
      'min_score' => $minScore,
      'max_score' => $maxScore
    ];

    $classResultInfo = ClassResultInfo::query()->updateOrCreate(
      $bindingData,
      $data
    );

    return $classResultInfo;
  }

  private function getTotalScore(Collection $courseResults)
  {
    $overallTotal = 0;
    $maxScore = 0;
    $minScore = $courseResults[0]?->result ?? 0;
    foreach ($courseResults as $key => $courseResult) {
      $result = $courseResult->result;
      if ($result > $maxScore) {
        $maxScore = $result;
      }
      if ($result < $minScore) {
        $minScore = $result;
      }
      $overallTotal += $result;
    }
    return [$overallTotal, $minScore, $maxScore];
  }
}
