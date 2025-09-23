<?php
namespace App\Actions\CourseResult;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\CourseResult;
use App\Models\Classification;
use App\Models\SessionResult;
use App\Models\Student;

class GenerateCourseSessionResult
{
  /** @var array<int, Course> $relatedCourses */
  private array $relatedCourses = [];
  /** @var array<string, StudentCourseSessionResult> $studentCourseSessionResults */
  private array $studentCourseSessionResults = [];

  /** @var array<int, SessionResult> $sessionResults */
  private $sessionResults;

  public function __construct(
    private Classification $classification,
    private AcademicSession $academicSession
  ) {
    // $this->sessionResults = SessionResult::query()
    $sessionResults = SessionResult::query()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->get();

    // If positions are not set, calculate and set them. You can remove this block at anytime, it's just a safety net
    if (empty($sessionResults->first()?->position)) {
      $sessionResults
        ->sortByDesc('average')
        ->each(function (SessionResult $sessionResult, $index) {
          $sessionResult->position = $index + 1;
          $sessionResult->save();
        });
    }
    $this->sessionResults = $sessionResults->keyBy('student_id');
    $this->run();
  }

  public function run()
  {
    $courseResults = CourseResult::where([
      'academic_session_id' => $this->academicSession->id,
      'classification_id' => $this->classification->id
    ])
      ->with('student.user', 'course')
      ->get();

    foreach ($courseResults as $key => $courseResult) {
      $sessionResult = $this->sessionResults[$courseResult->student_id] ?? null;
      if (empty($courseResult->student?->user) || empty($sessionResult)) {
        continue;
      }
      if (empty($this->relatedCourses[$courseResult->course_id])) {
        $this->relatedCourses[$courseResult->course_id] = $courseResult->course;
      }

      $studentCourseSessionResult =
        $this->studentCourseSessionResults[$courseResult->student_id] ??
        new StudentCourseSessionResult($courseResult->student, $sessionResult);
      $studentCourseSessionResult->addScore($courseResult);

      $this->studentCourseSessionResults[
        $courseResult->student_id
      ] = $studentCourseSessionResult;
    }
  }

  /**
   * @return Course[]
   */
  function getRelatedCourses()
  {
    return array_values($this->relatedCourses);
  }

  /**
   * @return array {
   *  [student_id]: array {
   *    [course_id]: array {
   *      first_term: ?float,
   *      second_term: ?float,
   *      third_term: ?float,
   *      total: float,
   *      average: float,
   *      student: Student,
   *      position: ?float,
   *    },
   *    session_result: SessionResult,
   *    session_result_data: {
   *      total: float,
   *      average: float,
   *      position: ?float,
   *    },
   *  }
   * }
   */
  public function getCourseSessionResults()
  {
    collect($this->studentCourseSessionResults)
      ->sortByDesc(fn(StudentCourseSessionResult $a) => $a->average)
      ->values()
      ->each(function (
        StudentCourseSessionResult $studentCourseSessionResult,
        $index
      ) {
        $studentCourseSessionResult->position = $index + 1;
      });
    $arr = [];
    foreach (
      $this->studentCourseSessionResults
      as $studentId => $studentCourseSessionResult
    ) {
      $arr[$studentId] = $studentCourseSessionResult->toArray();
    }
    return $arr;
  }
}

class StudentCourseSessionResult
{
  public float $total = 0;
  public float $average = 0;
  public ?float $position = null;
  /** @var array<int, CourseScoreData> $courseTotalScore The total score (sum of every term) for each course */
  public array $courseScores = [];
  public function __construct(
    private Student $student,
    private SessionResult $sessionResult
  ) {
  }

  public function addScore(CourseResult $courseResult)
  {
    $courseScoreData = $this->getCourseScoreData($courseResult);
    $courseScoreData->setScore($courseResult->term, $courseResult->result);
    $this->courseScores[$courseResult->course_id] = $courseScoreData;

    $this->setTotalAndAverage();
  }

  function setTotalAndAverage()
  {
    $this->total = 0;
    $this->average = 0;
    /** @var CourseScoreData $courseScoreData  */
    foreach ($this->courseScores as $courseId => $courseScoreData) {
      $this->total += $courseScoreData->average;
    }
    $coursesLen = count($this->courseScores);
    $this->average = $coursesLen > 0 ? $this->total / $coursesLen : 0;
  }

  private function getCourseScoreData(
    CourseResult $courseResult
  ): CourseScoreData {
    return $this->courseScores[$courseResult->course_id] ??
      new CourseScoreData($courseResult);
  }

  function toArray()
  {
    $arr = [
      'session_result' => $this->sessionResult,
      'session_result_data' => [
        'total' => round($this->total, 1),
        'average' => round($this->average, 2),
        'position' => $this->position
      ]
    ];
    foreach ($this->courseScores as $courseId => $courseScoreData) {
      $arr[$courseId] = $courseScoreData->toArray();
    }
    return $arr;
  }
}

class CourseScoreData
{
  public ?float $firstTerm = null;
  public ?float $secondTerm = null;
  public ?float $thirdTerm = null;
  public float $total = 0;
  public float $average = 0;

  public Student $student;

  public function __construct(CourseResult $courseResult)
  {
    $this->student = $courseResult->student;
  }

  function setScore(TermType $term, float $score)
  {
    if ($term == TermType::First) {
      $this->firstTerm = $score;
    } elseif ($term == TermType::Second) {
      $this->secondTerm = $score;
    } elseif ($term == TermType::Third) {
      $this->thirdTerm = $score;
    }
    $this->total = $this->firstTerm + $this->secondTerm + $this->thirdTerm;
    $this->setAverage();
  }

  function setAverage()
  {
    $divisor = 0;
    if (!is_null($this->firstTerm)) {
      $divisor++;
    }
    if (!is_null($this->secondTerm)) {
      $divisor++;
    }
    if (!is_null($this->thirdTerm)) {
      $divisor++;
    }
    $this->average = $divisor > 0 ? $this->total / $divisor : 0;
  }

  function toArray()
  {
    return [
      'first_term' => $this->firstTerm,
      'second_term' => $this->secondTerm,
      'third_term' => $this->thirdTerm,
      'total' => $this->total,
      'student' => $this->student,
      'average' => round($this->average, 1)
    ];
  }
}
