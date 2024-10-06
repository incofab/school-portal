<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Institution;
use Illuminate\Http\Request;

class DisplayEventController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    Event $event
  ) {
    $tokenUser = $this->getTokenUserFromCookie();

    $event->load([
      'eventCourseables.courseable.course',
      'exams' => fn($q) => $q
        ->where('examable_type', $tokenUser->getMorphClass())
        ->where('examable_id', $tokenUser->id)
    ]);
    if ($event->exams?->count() > 0) {
      return redirect(instRoute('external.home'));
    }
    return inertia('institutions/exams/external/display-event', [
      'event' => $event,
      'tokenUser' => $tokenUser
    ]);
  }
}
