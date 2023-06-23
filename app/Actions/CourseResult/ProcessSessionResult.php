<?php
namespace App\Actions\CourseResult;

use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Models\TermResult;
use Illuminate\Database\Eloquent\Collection;

class ProcessSessionResult
{
  private Institution $institution;
  public function __construct(
    private AcademicSession $academicSession,
    private Classification $classification
  ) {
    $this->institution = currentInstitution();
  }

  public static function run(
    AcademicSession $academicSession,
    Classification $classification
  ) {
    return (new self($academicSession, $classification))->execute();
  }

  private function execute()
  {
    $queryTermResults = TermResult::query()
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id);

    $termResults = (clone $queryTermResults)->get();
    $numOfTerms = (clone $queryTermResults)->groupBy('term')->count();
    $studentsTotal = $this->getTotalScoreByStudents($termResults);

    $this->persistSessionResult($studentsTotal, $numOfTerms);
  }

  private function getTotalScoreByStudents(Collection $termResults)
  {
    $studentsTotal = [];
    foreach ($termResults as $key => $termResult) {
      $studentTotalResult = $studentsTotal[$termResult->student_id] ?? 0;
      $studentsTotal[$termResult->student_id] =
        $studentTotalResult + $termResult->result;
    }
    return $studentsTotal;
  }

  private function persistSessionResult(
    array $studentsTotalScore,
    int $numOfTerms
  ) {
    if ($numOfTerms < 1) {
      return;
    }

    $bindingData = [
      'academic_session_id' => $this->academicSession->id,
      'institution_id' => $this->institution->id,
      'classification_id' => $this->classification->id
    ];
    foreach ($studentsTotalScore as $studentId => $totalScore) {
      $bindingData['student_id'] = $studentId;
      $average = $totalScore / $numOfTerms;
      $data = [
        'result' => $totalScore,
        'average' => $average,
        'grade' => GetGrade::run($average)
      ];
      SessionResult::query()->updateOrCreate($bindingData, $data);
    }
  }
}
