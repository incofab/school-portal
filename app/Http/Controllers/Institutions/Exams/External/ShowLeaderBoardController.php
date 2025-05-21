<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Institution;
use Illuminate\Http\Request;

class ShowLeaderBoardController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    ?Event $event = null
  ) {
    $tokenUser = $this->getTokenUserFromCookie();

    $event = $event
      ? $event
      : $institution
        ->events()
        ->getQuery()
        ->latest('id')
        ->first();
    abort_unless($event, 403, 'No event found');

    $leaderBoardExams = $institution
      ->exams()
      ->getQuery()
      ->where('exams.event_id', $event->id)
      ->where('exams.status', ExamStatus::Ended)
      ->with('examable')
      ->latest('score');

    $exams = $tokenUser
      ->exams()
      ->where('institution_id', $institution->id)
      ->get();
    return inertia('institutions/exams/external/show-leader-board', [
      'leaderBoardExams' => paginateFromRequest($leaderBoardExams),
      'tokenUser' => $tokenUser,
      'exams' => $exams,
      'event' => $event,
      'events' => $institution->events()->get()
    ]);
  }
  /* Old
  public function __invoke(Request $request, Institution $institution)
  {
    $tokenUser = $this->getTokenUserFromCookie();

    $leaderBoardExams = Exam::query()
      ->select(
        'exams.*',
        DB::raw('SUM(score) AS total_score'),
        DB::raw('COUNT(id) AS exam_count')
      )
      ->where('institution_id', $institution->id)
      ->where('exams.status', ExamStatus::Ended)
      ->with('examable')
      ->groupBy('examable_id', 'examable_type')
      ->orderBy('total_score', 'desc')
      ->get();

    $exams = $tokenUser
      ->exams()
      ->where('institution_id', $institution->id)
      ->get();

    return inertia('institutions/exams/external/show-leader-board', [
      'leaderBoardExams' => $leaderBoardExams,
      'tokenUser' => $tokenUser,
      'exams' => $exams
    ]);
  }
  */
}
