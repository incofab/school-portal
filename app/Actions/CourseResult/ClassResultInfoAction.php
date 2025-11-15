<?php
namespace App\Actions\CourseResult;

use App\Enums\TermType;
use App\Models\Classification;
use App\Models\CourseResult;
use App\Models\ClassResultInfo;
use DB;
use Illuminate\Database\Eloquent\Collection;

class ClassResultInfoAction
{
  public static function make()
  {
    return new self();
  }

  public function recalculate(ClassResultInfo $classResultInfo): ClassResultInfo
  {
    return $this->calculate(
      $classResultInfo->classification,
      $classResultInfo->academic_session_id,
      $classResultInfo->term,
      $classResultInfo->for_mid_term
    );
  }

  public function calculate(
    Classification $classification,
    int $academicSessionId,
    string|TermType $term,
    bool $forMidTerm,
    bool $forceCalculateTermResult = false
  ): ClassResultInfo {
    $queryCourseResults = CourseResult::query()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSessionId)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm);

    $classResultsGroupedByStudents = (clone $queryCourseResults)
      ->select('student_id')
      ->groupBy('student_id')
      ->get();
    $courseResultsGroupedByCourses = (clone $queryCourseResults)
      ->select('course_id')
      ->groupBy('course_id')
      ->get();

    $courseResults = $queryCourseResults->get();

    $numOfCourses = $courseResultsGroupedByCourses->count();
    $numOfStudents = $classResultsGroupedByStudents->count();

    abort_if($numOfCourses < 1, 421, 'There are no subjects in this selection');
    abort_if(
      $numOfStudents < 1,
      421,
      'There are no students in this selection'
    );

    [$totalScore, $minScore, $maxScore] = $this->getTotalScore($courseResults);

    $bindingData = [
      'institution_id' => $classification->institution_id,
      'term' => $term,
      'for_mid_term' => $forMidTerm,
      'academic_session_id' => $academicSessionId,
      'classification_id' => $classification->id
    ];

    $data = [
      'num_of_courses' => $numOfCourses,
      'num_of_students' => $numOfStudents,
      'total_score' => $totalScore,
      'max_obtainable_score' => $numOfCourses * 100,
      'average' => round($totalScore / $numOfCourses / $numOfStudents, 2),
      'min_score' => $minScore,
      'max_score' => $maxScore
    ];

    DB::beginTransaction();
    $classResultInfo = ClassResultInfo::query()->updateOrCreate(
      $bindingData,
      $data
    );

    ProcessTermResult::run(
      $classification->institution,
      $classResultInfo,
      $forceCalculateTermResult
    );
    DB::commit();

    return $classResultInfo;
  }

  /**
   * @param Collection<int, CourseResult> $courseResults
   */
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
