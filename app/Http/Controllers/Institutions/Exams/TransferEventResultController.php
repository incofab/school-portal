<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Actions\Event\EventResultHandler;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\Institution;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class TransferEventResultController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function __invoke(
    Institution $institution,
    Event $event,
    Request $request
  ) {
    $event->load('eventCourseables.courseable');
    abort_if(
      $event->eventCourseables->count() > 1,
      400,
      'Use the multiple transfer event result route instead'
    );
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

    $courseTeacher = $existsRuleCourseTeacher->getModel();

    (new EventResultHandler(
      $courseTeacher,
      collect($data)
        ->except('assessment_id', 'course_teacher_id')
        ->toArray(),
      $event->eventCourseables->first(),
      $existsRuleAssessment->getModel()
    ))->transferEventResult($event);

    return $this->ok();
  }
}
