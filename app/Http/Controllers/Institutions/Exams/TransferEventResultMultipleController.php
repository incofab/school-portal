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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TransferEventResultMultipleController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function create(Institution $institution, Event $event)
  {
    $event->load('eventCourseables.courseable.course');

    return Inertia::render(
      'institutions/exams/transfer-event-results-multiple',
      [
        'event' => $event,
        'assessments' => Assessment::all()
      ]
    );
  }

  public function store(
    Institution $institution,
    Event $event,
    Request $request
  ) {
    $eventCourseables = $event
      ->eventCourseables()
      ->get()
      ->keyBy('id');

    $data = $request->validate([
      'institution_id' => ['required'],
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'term' => ['required', new Enum(TermType::class)],
      'event_courseables' => [
        'required',
        'array',
        'size:' . $eventCourseables->count()
      ],
      'event_courseables.*.event_courseable_id' => [
        'required',
        'distinct',
        Rule::in($eventCourseables->keys()->toArray())
      ],
      'event_courseables.*.for_mid_term' => ['required', 'boolean'],
      'event_courseables.*.assessment_id' => [
        'nullable',
        new ValidateExistsRule(Assessment::class)
      ],
      'event_courseables.*.course_teacher_id' => [
        'required',
        new ValidateExistsRule(CourseTeacher::class)
      ]
    ]);

    $courseTeachers = CourseTeacher::query()
      ->whereIn(
        'id',
        collect($data['event_courseables'])
          ->pluck('course_teacher_id')
          ->unique()
          ->filter()
          ->values()
          ->toArray()
      )
      ->get()
      ->keyBy('id');

    if (currentInstitutionUser()->isTeacher()) {
      $currentUserId = currentUser()?->id;
      $invalidCourseTeacher = $courseTeachers->first(
        fn($courseTeacher) => $courseTeacher->user_id !== $currentUserId
      );

      if ($invalidCourseTeacher) {
        throw ValidationException::withMessages([
          'course_teacher_id' => 'You can only transfer results for your course'
        ]);
      }
    }

    $assessments = Assessment::query()
      ->whereIn(
        'id',
        collect($data['event_courseables'])
          ->pluck('assessment_id')
          ->unique()
          ->filter()
          ->values()
          ->toArray()
      )
      ->get()
      ->keyBy('id');

    $baseData = collect($data)
      ->only(['institution_id', 'academic_session_id', 'term'])
      ->toArray();

    foreach ($data['event_courseables'] as $payload) {
      (new EventResultHandler(
        $courseTeachers[$payload['course_teacher_id']],
        [...$baseData, 'for_mid_term' => $payload['for_mid_term']],
        $eventCourseables[$payload['event_courseable_id']],
        $assessments[$payload['assessment_id']] ?? null
      ))->transferEventResult($event);
    }
    return $this->ok();
  }
}
