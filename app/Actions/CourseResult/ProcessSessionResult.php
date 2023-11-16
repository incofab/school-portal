<?php
namespace App\Actions\CourseResult;

use App\DTO\FullTermResult;
use App\Enums\TermType;
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
      ->select('term_results.*')
      ->where('for_mid_term', false)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id);

    $termResults = (clone $queryTermResults)->get();
    $availableTerms = (clone $queryTermResults)
      ->groupBy('term')
      ->pluck('term')
      ->toArray();
    [$studentsTotal, $mappedTermResult] = $this->getTotalScoreByStudents(
      $termResults
    );

    $this->persistSessionResult(
      $studentsTotal,
      $mappedTermResult,
      $availableTerms
    );
  }

  private function getTotalScoreByStudents(Collection $termResults)
  {
    $studentsTotal = [];
    $mappedTermResult = [];
    /** @var TermResult $termResult */
    foreach ($termResults as $key => $termResult) {
      $studentTotalResult = $studentsTotal[$termResult->student_id] ?? 0;
      $studentsTotal[$termResult->student_id] =
        $studentTotalResult + $termResult->total_score;

      $studentMappedTermResult =
        $mappedTermResult[$termResult->student_id] ?? new FullTermResult();
      $studentMappedTermResult->setTermResult($termResult);
      $mappedTermResult[$termResult->student_id] = $studentMappedTermResult;
    }
    return [$studentsTotal, $mappedTermResult];
  }

  /**
   * @param array<string, FullTermResult> $mappedTermResult
   */
  private function persistSessionResult(
    array $studentsTotalScore,
    array $mappedTermResult,
    $availableTerms
  ) {
    if (in_array(TermType::Third, $availableTerms)) {
      return;
    }

    $bindingData = [
      'academic_session_id' => $this->academicSession->id,
      'institution_id' => $this->institution->id,
      'classification_id' => $this->classification->id
    ];

    foreach ($studentsTotalScore as $studentId => $totalScore) {
      $studentMappedTermResult = $mappedTermResult[$studentId];
      $bindingData['student_id'] = $studentId;
      $average = $studentMappedTermResult->getAverage();

      $data = [
        'result' => $studentMappedTermResult->getTotal(),
        'average' => $average,
        'grade' => GetGrade::run($average)
      ];
      SessionResult::query()->updateOrCreate($bindingData, $data);
    }
  }
}
