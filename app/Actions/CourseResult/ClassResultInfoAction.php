<?php
namespace App\Actions\CourseResult;

use App\Models\CourseResult;
use App\Models\ClassResultInfo;
use Illuminate\Database\Eloquent\Collection;

class ClassResultInfoAction
{
  function __construct(private CourseResult $courseResult)
  {
  }

  public static function make(CourseResult $courseResult)
  {
    return new self($courseResult);
  }

  public function firstOrCreate(): ClassResultInfo
  {
    $classResultInfo = ClassResultInfo::query()
      ->where('course_id', $this->courseResult->course_id)
      ->where('classification_id', $this->courseResult->classification_id)
      ->where('academic_session_id', $this->courseResult->academic_session_id)
      ->where('term', $this->courseResult->term)
      ->first();

    $classResultInfo = !empty($classResultInfo)
      ? $classResultInfo
      : $this->recalculate();

    return $classResultInfo;
  }

  public function recalculate(): ClassResultInfo
  {
    $queryCourseResults = CourseResult::query()
      ->where('classification_id', $this->courseResult->classification_id)
      ->where('academic_session_id', $this->courseResult->academic_session_id)
      ->where('term', $this->courseResult->term);

    $courseResults = $queryCourseResults->get();
    $courseResultsGroupedByCourses = $queryCourseResults
      ->groupBy('course_id')
      ->get();
    $classResultsGroupedByStudents = $queryCourseResults
      ->groupBy('student_id')
      ->get();

    $numOfCourses = $courseResultsGroupedByCourses->count();

    [$totalScore, $minScore, $maxScore] = $this->getTotalScore($courseResults);

    $bindingData = [
      'institution_id' => $this->courseResult->institution_id,
      'term' => $this->courseResult->term,
      'academic_session_id' => $this->courseResult->academic_session_id,
      'course_id' => $this->courseResult->id,
      'classification_id' => $this->courseResult->classification_id
    ];

    $data = [
      'num_of_courses' => $numOfCourses,
      'num_of_students' => $classResultsGroupedByStudents->count(),
      'total_score' => $totalScore,
      'max_obtainable_score' => $numOfCourses * 100,
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
    $minScore = 0;
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
