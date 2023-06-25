<?php
namespace App\Actions\CourseResult;

use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

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
    // $studentsTotal = $this->getTotalScoreByStudents($courseResults);
    $studentsResultDetails = $this->prepareStudentResult($courseResults);

    // $this->persistTermResult($studentsTotal);
    $this->persistTermResult($studentsResultDetails);
    ProcessSessionResult::run(
      $this->classResultInfo->academicSession,
      $this->classResultInfo->classification
    );
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

  /** @return array<string, ResultDetail> */
  private function prepareStudentResult(Collection $courseResults)
  {
    $studentsResultDetails = [];
    foreach ($courseResults as $key => $courseResult) {
      if (empty($studentsResultDetails[$courseResult->student_id])) {
        $studentsResultDetails[$courseResult->student_id] = new ResultDetail(
          $courseResult->student_id
        );
      }
      $studentResultDetail = $studentsResultDetails[$courseResult->student_id];

      $studentResultDetail->update($courseResult->result);
    }
    return $studentsResultDetails;
  }

  /** @param array<string, ResultDetail> $studentsResultDetails */
  private function persistTermResult(array $studentsResultDetails)
  {
    $this->checkForEqualNumOfSubjects(
      $studentsResultDetails,
      $this->classResultInfo->classification
    );

    $studentsTotalAverageScores = array_map(
      fn(ResultDetail $item) => [
        $item->getStudentId() => $item->getAverageScore()
      ],
      $studentsResultDetails
    );

    // Sorts the students according to position
    arsort($studentsTotalAverageScores);

    $bindingData = [
      'institution_id' => $this->institution->id,
      'classification_id' => $this->classResultInfo->classification_id,
      'academic_session_id' => $this->classResultInfo->academic_session_id,
      'term' => $this->classResultInfo->term
    ];

    $index = 0;
    foreach ($studentsTotalAverageScores as $studentId => $averageScore) {
      /** @var ResultDetail $resultDetail  */
      $resultDetail = $studentsResultDetails[$studentId];
      $bindingData['student_id'] = $studentId;
      $data = [
        'position' => $index + 1,
        'total_score' => $resultDetail->getTotalScore(),
        'average' => $resultDetail->getAverageScore()
        // 'remark' => '',
      ];
      TermResult::query()->updateOrCreate($bindingData, $data);
      $index += 1;
    }
  }

  /** @param array<string, ResultDetail> $studentsResultDetails */
  private function checkForEqualNumOfSubjects(
    array $studentsResultDetails,
    Classification $classification
  ) {
    $totalClassStudents = Student::query()
      ->where('classification_id', $classification->id)
      ->count();

    if (count($studentsResultDetails) !== $totalClassStudents) {
      return throw ValidationException::withMessages([
        'error' => 'You have to record results for all students first'
      ]);
    }

    if (!$classification->has_equal_subjects) {
      return true;
    }
    $hasUnequalNumOfSubjects = false;
    $numOfCourses = -1;
    /** @var ResultDetail $studentResultDetail */
    foreach ($studentsResultDetails as $key => $studentResultDetail) {
      if (
        $numOfCourses !== -1 &&
        $numOfCourses !== $studentResultDetail->getNumOfCourses()
      ) {
        $hasUnequalNumOfSubjects = true;
        break;
      }
      $numOfCourses = $studentResultDetail->getNumOfCourses();
    }
    if (!$hasUnequalNumOfSubjects) {
      return true;
    }
    return throw ValidationException::withMessages([
      'error' => 'Some students have unrecorded results'
    ]);
  }
}

class ResultDetail
{
  private float $averageScore = 0;
  private float $totalScore = 0;
  private int $numOfCourses = 0;

  function __construct(private int $studentId)
  {
  }

  function update(float $resultScore)
  {
    $this->numOfCourses += 1;
    $this->totalScore += $resultScore;
    $this->averageScore = $this->totalScore / $this->numOfCourses;
  }

  function getStudentId()
  {
    return $this->studentId;
  }
  function getAverageScore()
  {
    return $this->averageScore;
  }
  function getTotalScore()
  {
    return $this->totalScore;
  }
  function getNumOfCourses()
  {
    return $this->numOfCourses;
  }
}
