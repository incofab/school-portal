<?php
namespace App\Actions\CourseResult;

use App\Models\Classification;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use DB;
use Illuminate\Database\Eloquent\Collection;

class EvaluateCourseResultForClass
{
  public function __construct(
    private Classification $classification,
    private $courseId,
    private $academicSessionId,
    private string $term
  ) {
  }

  public static function run(
    Classification $classification,
    $courseId,
    $academicSessionId,
    string $term
  ) {
    return (new self(
      $classification,
      $courseId,
      $academicSessionId,
      $term
    ))->execute();
  }

  private function execute()
  {
    $courseResults = CourseResult::query()
      ->where('institution_id', $this->classification->institution_id)
      ->where('course_id', $this->courseId)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSessionId)
      ->where('term', $this->term)
      ->get();

    [
      $totalScore,
      $minScore,
      $maxScore,
      $scoreByStudents
    ] = $this->getTotalScore($courseResults);

    $bindingData = [
      'institution_id' => $this->classification->institution_id,
      'term' => $this->term,
      'academic_session_id' => $this->academicSessionId,
      'course_id' => $this->courseId,
      'classification_id' => $this->classification->id
    ];

    $numOfStudents = $courseResults->count();
    $data = [
      'total_score' => $totalScore,
      'max_obtainable_score' => 100,
      'average' => round($totalScore / $numOfStudents, 2),
      'min_score' => $minScore,
      'max_score' => $maxScore
    ];

    DB::beginTransaction();
    $courseResultInfo = CourseResultInfo::query()->updateOrCreate(
      $bindingData,
      $data
    );
    $this->recordCoursePosition($scoreByStudents);
    DB::commit();
  }

  private function getTotalScore(Collection $courseResults)
  {
    $overallTotal = 0;
    $maxScore = 0;
    $minScore = $courseResults[0]?->result ?? 0;
    $scoreByStudents = [];
    foreach ($courseResults as $key => $courseResult) {
      $result = $courseResult->result;
      $scoreByStudents[$courseResult->student_id] = $result;
      if ($result > $maxScore) {
        $maxScore = $result;
      }
      if ($result < $minScore) {
        $minScore = $result;
      }
      $overallTotal += $result;
    }
    return [$overallTotal, $minScore, $maxScore, $scoreByStudents];
  }

  private function recordCoursePosition(array $scoreByStudents)
  {
    arsort($scoreByStudents);
    $bindingData = [
      'institution_id' => $this->classification->institution_id,
      'term' => $this->term,
      'academic_session_id' => $this->academicSessionId,
      'course_id' => $this->courseId,
      'classification_id' => $this->classification->id
    ];
    $index = 0;
    foreach ($scoreByStudents as $studentId => $score) {
      $bindingData['student_id'] = $studentId;
      $data = [
        'position' => $index + 1
      ];
      CourseResult::query()->updateOrCreate($bindingData, $data);
      $index += 1;
    }
  }
}
