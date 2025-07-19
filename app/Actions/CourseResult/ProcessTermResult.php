<?php

namespace App\Actions\CourseResult;

use App\Actions\ResultUtil;
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
  // private Institution $institution;
  public function __construct(
    private Institution $institution,
    private ClassResultInfo $classResultInfo,
    private bool $forceCalculateTermResult = false
  ) {
    // $this->institution = currentInstitution();
  }

  public static function run(
    Institution $institution,
    ClassResultInfo $classResultInfo,
    bool $forceCalculateTermResult = false
  ) {
    return (new self(
      $institution,
      $classResultInfo,
      $forceCalculateTermResult
    ))->execute();
  }

  private function execute()
  {
    $courseResults = CourseResult::query()
      ->where('classification_id', $this->classResultInfo->classification_id)
      ->where(
        'academic_session_id',
        $this->classResultInfo->academic_session_id
      )
      ->where('term', $this->classResultInfo->term)
      ->where('for_mid_term', $this->classResultInfo->for_mid_term)
      ->get();

    $studentsResultDetails = $this->prepareStudentResult($courseResults);

    $this->persistTermResult($studentsResultDetails);
    ProcessSessionResult::run(
      $this->classResultInfo->academicSession,
      $this->classResultInfo->classification
    );
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

    $studentsTotalAverageScores = [];
    foreach ($studentsResultDetails as $key => $item) {
      $studentsTotalAverageScores[
        $item->getStudentId()
      ] = $item->getAverageScore();
    }

    $bindingData = [
      'institution_id' => $this->institution->id,
      'classification_id' => $this->classResultInfo->classification_id,
      'academic_session_id' => $this->classResultInfo->academic_session_id,
      'term' => $this->classResultInfo->term,
      'for_mid_term' => $this->classResultInfo->for_mid_term
    ];

    // Sorts the students according to position
    arsort($studentsTotalAverageScores);
    $assignedPositions = ResultUtil::assignPositions(
      $studentsTotalAverageScores
    );
    foreach ($assignedPositions as $key => $assignedPosition) {
      /** @var ResultDetail $resultDetail  */
      $resultDetail = $studentsResultDetails[$assignedPosition->getId()];
      $bindingData['student_id'] = $assignedPosition->getId();
      $data = [
        'position' => $assignedPosition->getPosition(),
        'total_score' => $resultDetail->getTotalScore(),
        'average' => $resultDetail->getAverageScore()
        // 'remark' => '',
      ];
      TermResult::query()->updateOrCreate($bindingData, $data);
    }
  }

  /** @param array<string, ResultDetail> $studentsResultDetails */
  private function checkForEqualNumOfSubjects(
    array $studentsResultDetails,
    Classification $classification
  ) {
    if (
      !$classification->has_equal_subjects ||
      $this->forceCalculateTermResult
    ) {
      return true;
    }

    $totalClassStudents = Student::query()
      ->where('classification_id', $classification->id)
      ->count();

    if (
      !$this->forceCalculateTermResult &&
      count($studentsResultDetails) !== $totalClassStudents
    ) {
      throw ValidationException::withMessages([
        'error' => 'You have to record results for all students first'
      ]);
    }

    $incompleteStudentResultDetail = null;
    $numOfCourses = -1;
    /** @var ResultDetail $studentResultDetail */
    foreach ($studentsResultDetails as $key => $studentResultDetail) {
      if (
        $numOfCourses !== -1 &&
        $numOfCourses !== $studentResultDetail->getNumOfCourses()
      ) {
        $incompleteStudentResultDetail = $studentResultDetail;
        break;
      }
      $numOfCourses = $studentResultDetail->getNumOfCourses();
    }
    if (!$incompleteStudentResultDetail) {
      return true;
    }
    $affectedStudent = $incompleteStudentResultDetail->getStudent();
    throw ValidationException::withMessages([
      'error' => "Some students ({$affectedStudent->user?->full_name}) have unrecorded results"
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
  function getStudent(): Student
  {
    return Student::where('id', $this->getStudentId())
      ->with('user')
      ->first();
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
