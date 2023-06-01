<?php
namespace App\Actions\CourseResult;

use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\TermResult;
use Illuminate\Database\Eloquent\Collection;

class ProcessTermResult
{
  private Institution $institution;
  public function __construct(private ClassResultInfo $classResultInfo)
  {
    $this->institution = currentInstitution();
  }

  public static function run(ClassResultInfo $classResultInfo)
  {
    return (new self($classResultInfo))->execute();
  }

  private function execute()
  {
    $queryCourseResults = CourseResult::query()
      ->where('classification_id', $this->classResultInfo->classification_id)
      ->where(
        'academic_session_id',
        $this->classResultInfo->academic_session_id
      )
      ->where('term', $this->classResultInfo->term);

    $courseResults = $queryCourseResults->get();
    $studentsTotal = $this->getTotalScoreByStudents($courseResults);

    $this->persistTermResult($studentsTotal);
  }

  private function getTotalScoreByStudents(Collection $courseResults)
  {
    $studentsTotal = [];
    foreach ($courseResults as $key => $courseResult) {
      $studentTotalResult = $studentsTotal[$courseResult->student_id] ?? 0;
      $studentsTotal[$courseResult->student_id] =
        $studentTotalResult + $courseResult->result;
    }
    return $studentsTotal;
  }

  private function persistTermResult(array $studentsTotalScore)
  {
    arsort($studentsTotalScore);
    $bindingData = [
      'institution_id' => $this->institution->id,
      'classification_id' => $this->classResultInfo->classification_id,
      'academic_session_id' => $this->classResultInfo->academic_session_id,
      'term' => $this->classResultInfo->term
    ];
    $index = 0;
    foreach ($studentsTotalScore as $studentId => $totalScore) {
      $bindingData['student_id'] = $studentId;
      $data = [
        'total_score' => $totalScore,
        'position' => $index + 1,
        'average' => $totalScore / $this->classResultInfo->num_of_courses
        // 'remark' => '',
      ];
      TermResult::query()->updateOrCreate($bindingData, $data);
      $index += 1;
    }
  }
}
