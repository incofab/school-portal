<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Actions\CreateExam;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Models\Event;
use App\Models\Institution;
use App\Models\TokenUser;
use App\Support\MorphMap;
use Inertia\Inertia;

class ExamExternalController extends Controller
{
  private function validateCreateExam(Event $event)
  {
    [$status, $message] = $event->canCreateExamCheck();
    abort_unless($status, 403, $message);
  }

  function create(Institution $institution, Event $event)
  {
    $this->validateCreateExam($event);
    $tokenUser = $this->getTokenUserFromCookie();
    return Inertia::render('institutions/exams/external/create-exam-external', [
      'event' => $event->load('eventCourseables.courseable.course'),
      'tokenUser' => $tokenUser,
      'examable_type' => MorphMap::key(TokenUser::class)
    ]);
  }

  function store(
    StoreExamRequest $request,
    Institution $institution,
    Event $event
  ) {
    $this->validateCreateExam($event);
    $exam = CreateExam::run($event, $request->validated());
    return $this->ok(['exam' => $exam]);
  }
}
