<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Actions\Event\EventResultHandler;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\Institution;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TransferEventResultController extends Controller
{
  public function __invoke(
    Institution $institution,
    Event $event,
    Request $request
  ) {
    $existsRuleAssessment = new ValidateExistsRule(Assessment::class);
    $existsRuleCourseTeacher = new ValidateExistsRule(CourseTeacher::class);
    $data = $request->validate([
      'institution_id' => ['required'],
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'term' => ['required', new Enum(TermType::class)],
      'for_mid_term' => ['required', 'boolean'],
      'assessment_id' => ['nullable', $existsRuleAssessment],
      'course_teacher_id' => ['required', $existsRuleCourseTeacher]
    ]);

    (new EventResultHandler(
      $existsRuleCourseTeacher->getModel(),
      $data,
      $existsRuleAssessment->getModel()
    ))->transferEventResult($event);

    return $this->ok();
  }
}
