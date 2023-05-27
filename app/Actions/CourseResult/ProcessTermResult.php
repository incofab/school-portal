<?php
namespace App\Actions\CourseResult;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Institution;
use App\Models\TermResult;
use Illuminate\Database\Eloquent\Collection;

class ProcessTermResult
{
  private Institution $institution;
  public function __construct(
    private TermType $term,
    private AcademicSession $academicSession,
    private Classification $classification
  ) {
    $this->institution = currentInstitution();
  }

  public static function run(
    TermType $term,
    AcademicSession $academicSession,
    Classification $classification
  ) {
    return (new self($term, $academicSession, $classification))->execute();
  }

  private function execute()
  {
    $queryCourseResults = $this->classification
      ->courseResults()
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', $this->term);

    $courseResults = $queryCourseResults->get();

    $studentsTotal = $this->getTotalScoreByStudents($courseResults);

    $classResultInfo = ClassResultInfoAction::make(
      $courseResults->first()
    )->firstOrCreate();
    $this->persistTermResult($studentsTotal, $classResultInfo);
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

  private function persistTermResult(
    array $studentsTotalScore,
    ClassResultInfo $classResultInfo
  ) {
    $bindingData = [
      'institution_id' => $this->institution->id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term
    ];
    foreach ($studentsTotalScore as $studentId => $totalScore) {
      $bindingData['student_id'] = $studentId;
      $data = [
        'result' => $totalScore,
        'average' => $totalScore / $classResultInfo->num_of_courses,
        'grade' => GetGrade::run($totalScore)
      ];
      TermResult::query()->updateOrCreate($bindingData, $data);
    }
  }
}
