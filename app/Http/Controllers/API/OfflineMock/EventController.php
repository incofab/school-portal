<?php

namespace App\Http\Controllers\API\OfflineMock;

use App\Actions\OfflineMock\MockEventHandler;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Institution;
use Illuminate\Http\Request;

class EventController extends Controller
{
  function index(Institution $institution, Request $request)
  {
    $latestEventId = $request->latest_event_id ?? 0;
    $events = Event::query()
      ->where('institution_id', $institution->id)
      ->where('id', '>', $latestEventId)
      ->with('eventCourseables.courseable.course')
      ->latest('id')
      ->take(100)
      ->get()
      ->map(function (Event $event) {
        return MockEventHandler::make()->formatEvent($event);
      })
      ->toArray();

    return $this->successApiRes($events);
  }

  function show(Institution $institution, Event $event)
  {
    $event->load('eventCourseables.courseable.course');
    return $this->successApiRes(MockEventHandler::make()->formatEvent($event));
  }

  function deepShow(Institution $institution, Event $event)
  {
    $event->load(
      'eventCourseables.courseable.course',
      'eventCourseables.courseable.passages',
      'eventCourseables.courseable.instructions',
      'eventCourseables.courseable.questions'
    );
    return $this->successApiRes(
      MockEventHandler::make()->formatEvent($event, true)
    );
  }
}
