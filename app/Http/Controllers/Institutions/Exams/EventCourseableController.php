<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventCourseableController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  function index(Institution $institution, Event $event)
  {
    $query = $event
      ->eventCourseables()
      ->getQuery()
      ->with([
        'courseable' => function (MorphTo $morphTo) {
          $morphTo->morphWith([CourseSession::class => ['course']]);
        }
      ]);

    return Inertia::render('institutions/exams/list-event-courseables', [
      'eventCourseables' => paginateFromRequest($query->latest('id')),
      'event' => $event,
      'courses' => $institution
        ->courses()
        ->with('sessions')
        ->get()
    ]);
  }

  function create(Institution $institution, Event $event)
  {
    return Inertia::render(
      'institutions/events/create-edit-event-courseables',
      [
        'event' => $event,
        'event_courseables' => $event
          ->eventCourseables()
          ->with('courseable')
          ->get()
      ]
    );
  }

  function store(Request $request, Institution $institution, Event $event)
  {
    $data = $request->validate(EventCourseable::createRule());

    $event->eventCourseables()->updateOrCreate($data);

    return $this->ok();
  }

  function destroy(Institution $institution, EventCourseable $eventCourseable)
  {
    $eventCourseable->delete();
    return $this->ok();
  }
}
