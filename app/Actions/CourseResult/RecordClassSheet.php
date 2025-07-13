<?php
namespace App\Actions\CourseResult;

use App\Models\CourseTeacher;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\User;
use DB;

class RecordClassSheet
{
  private $academicSessionId;
  private $term;
  private $forMidTerm;
  /**
   * @param array{
   *  classification_id: int,
   *  academic_session_id: int,
   *  term: string,
   *  for_mid_term?: bool,
   *  class_results: array {
   *    student_id: int,
   *    results: array {
   *      course_id: int,
   *      score: float,
   *    },
   *  },
   * }[] $data
   */
  public function __construct(
    private Institution $institution,
    private $data,
    private User $user,
    private Classification $classification
  ) {
    $this->academicSessionId = $data['academic_session_id'];
    $this->term = $data['term'];
    $this->forMidTerm = $data['for_mid_term'] ?? false;
  }

  public function run()
  {
    DB::beginTransaction();

    foreach ($this->data['class_results'] as $key => $studentResults) {
      foreach ($studentResults['results'] as $key => $studentResult) {
        RecordCourseResult::run(
          [
            'student_id' => $studentResults['student_id'],
            'academic_session_id' => $this->academicSessionId,
            'institution_id' => $this->institution->id,
            'term' => $this->term,
            'for_mid_term' => $this->forMidTerm,
            'course_id' => $studentResult['course_id'],
            'exam' => $studentResult['score']
          ],
          new CourseTeacher([
            'course_id' => $studentResult['course_id'],
            'classification_id' => $this->classification->id,
            'classification' => $this->classification,
            'user_id' => $this->user->id
          ]),
          false
        );
      }
    }

    $courses = $this->institution->courses()->get();
    foreach ($courses as $course) {
      EvaluateCourseResultForClass::run(
        $this->classification,
        $course->id,
        $this->academicSessionId,
        $this->term,
        $this->forMidTerm
      );
    }

    try {
      ClassResultInfoAction::make()->calculate(
        $this->classification,
        $this->academicSessionId,
        $this->term,
        $this->forMidTerm,
        forceCalculateTermResult: true
      );
    } catch (\Throwable $th) {
      info('Calculate error: ' . $th->getMessage());
      // Even if there's an error, don't stop the process, we can alays recalculate
    }
    DB::commit();
  }
}
