<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Actions\CreateExam;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Models\Event;
use App\Models\Institution;
use App\Support\MorphMap;
use Inertia\Inertia;

class ExamExternalController extends Controller
{
  function create(Institution $institution, Event $event)
  {
    $tokenUser = $this->getTokenUserFromCookie();
    return Inertia::render('institutions/exams/external/create-exam-external', [
      'event' => $event->load('eventCourseables.courseable.course'),
      'tokenUser' => $tokenUser,
      'examable_type' => MorphMap::key(User::class)
    ]);
  }

  function store(
    StoreExamRequest $request,
    Institution $institution,
    Event $event
  ) {
    $exam = CreateExam::run($event, $request->validated());
    return $this->ok(['exam' => $exam]);
  }
}
