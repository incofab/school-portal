<?php

namespace App\Actions\CourseResult;

use App\Models\CourseResult;
use App\Models\TermResult;

class TermResultDeleteHandler
{
  public function __construct(private TermResult $termResult)
  {
  }

  function delete()
  {
    CourseResult::query()
      ->where([
        'academic_session_id' => $this->termResult->academic_session_id,
        'student_id' => $this->termResult->student_id,
        // 'course_id' => $this->termResult->course_id,
        'term' => $this->termResult->term,
        'for_mid_term' => $this->termResult->for_mid_term,
        'classification_id' => $this->termResult->classification_id
      ])
      ->delete();

    $this->termResult->delete();
    $existingTermResult = TermResult::query()
      ->where([
        'academic_session_id' => $this->termResult->academic_session_id,
        'student_id' => $this->termResult->student_id,
        'term' => $this->termResult->term,
        'for_mid_term' => $this->termResult->for_mid_term ?? false,
        'classification_id' => $this->termResult->classification_id
      ])
      ->exists();

    if ($existingTermResult) {
      $this->reProcessResult();
    }
  }

  function restore()
  {
    $this->termResult->restore();
    CourseResult::query()
      ->where([
        'academic_session_id' => $this->termResult->academic_session_id,
        'student_id' => $this->termResult->student_id,
        // 'course_id' => $this->termResult->course_id,
        'term' => $this->termResult->term,
        'for_mid_term' => $this->termResult->for_mid_term,
        'classification_id' => $this->termResult->classification_id
      ])
      ->withTrashed()
      ->restore();
    $this->reProcessResult();
  }

  private function reProcessResult()
  {
    ClassResultInfoAction::make()->calculate(
      classification: $this->termResult->classification,
      academicSessionId: $this->termResult->academic_session_id,
      term: $this->termResult->term,
      forMidTerm: $this->termResult->for_mid_term,
      forceCalculateTermResult: true
    );
  }
}
