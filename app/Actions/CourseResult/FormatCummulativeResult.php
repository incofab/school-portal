<?php
namespace App\Actions\CourseResult;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermResult;

class FormatCummulativeResult
{
  private Institution $institution;
  function __construct(
    private AcademicSession $academicSession,
    private Classification $classification,
    private string|null $term
  ) {
    $this->institution = currentInstitution();
  }

  public static function run(
    AcademicSession $academicSession,
    Classification $classification,
    string|null $term
  ) {
    return (new self($academicSession, $classification, $term))->execute();
  }

  public function execute()
  {
    $sessionResults = $this->institution
      ->sessionResults()
      ->where('academic_session_id', $this->academicSession->id)
      ->where('classification_id', $this->classification->id)
      // ->when($this->term, fn($q, $value) => $q->where('term', $value))
      ->with('student.user')
      ->with(
        'student.termResults',
        fn($q) => $q
          ->where('academic_session_id', $this->academicSession->id)
          ->when($this->term, fn($q, $value) => $q->where('term', $value))
      )
      ->with(
        'student.courseResults',
        fn($q) => $q
          ->where('academic_session_id', $this->academicSession->id)
          ->when($this->term, fn($q, $value) => $q->where('term', $value))
      )
      ->get();
    // dd(json_encode($sessionResult, JSON_PRETTY_PRINT));
    $sessionResultsArr = [];
    foreach ($sessionResults as $key => $sessionResult) {
      // $sessionResultsArr[$sessionResult->student_id]
      if ($sessionResult->student) {
        $sessionResultsArr[] = StudentSessionResultFormat::run($sessionResult);
      }
    }

    return [
      'sessionResults' => $sessionResultsArr,
      'courses' => $this->getCourses()
    ];
  }

  function getCourses()
  {
    $query = Course::query()
      ->select('courses.*')
      ->join('course_results', 'courses.id', 'course_results.course_id')
      ->where('course_results.academic_session_id', $this->academicSession->id)
      ->where('course_results.classification_id', $this->classification->id);
    return [
      'firstTermCourses' => (clone $query)
        ->where('course_results.term', TermType::First)
        ->groupBy('course_results.course_id')
        ->get(),
      'secondTermCourses' => (clone $query)
        ->where('course_results.term', TermType::Second)
        ->groupBy('course_results.course_id')
        ->get(),
      'thirdTermCourses' => (clone $query)
        ->where('course_results.term', TermType::Third)
        ->groupBy('course_results.course_id')
        ->get()
    ];
  }
}

class StudentSessionResultFormat
{
  private Student $student;
  private array $firstTermCourseResult = [];
  private array $secondTermCourseResult = [];
  private array $thirdTermCourseResult = [];
  private TermResult $firstTermResult;
  private TermResult $secondTermResult;
  private TermResult $thirdTermResult;

  function __construct(SessionResult $sessionResult)
  {
    $this->student = $sessionResult->student;
    $this->assignTermResults($this->student->termResults);
    $this->assignCourseResults($this->student->courseResults);
  }

  static function run(SessionResult $sessionResult)
  {
    $obj = new self($sessionResult);
    // dd(get_object_vars($obj));
    return get_object_vars($obj);
  }

  private function assignTermResults($termResults)
  {
    /** @var TermResult $termResult */
    foreach ($termResults as $key => $termResult) {
      $termResultProperty = $termResult->term->value . 'TermResult';
      $this->{$termResultProperty} = $termResult;
    }
  }

  private function assignCourseResults($courseResults)
  {
    /** @var CourseResult $courseResult */
    foreach ($courseResults as $key => $courseResult) {
      $this->{$courseResult->term->value . 'TermCourseResult'}[
        $courseResult->course_id
      ] = $courseResult;
    }
  }
}
