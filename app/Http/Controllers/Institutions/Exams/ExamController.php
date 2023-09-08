<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Actions\CreateExam;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Institution;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  function index(Institution $institution, Event $event, Request $request)
  {
    $query = $event
      ->exams()
      ->getQuery()
      ->withCount('examCourseables');

    return Inertia::render('institutions/exams/list-exams', [
      'exams' => paginateFromRequest($query->latest('id')),
      'event' => $event
    ]);
  }

  function create(Institution $institution, Event $event)
  {
    return Inertia::render('institutions/exams/create-exam', [
      'event' => $event->load('eventCourseables.courseable.course'),
      'external_reference' => request('reference')
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

  function destroy(Institution $institution, Exam $exam)
  {
    $exam->delete();
    return $this->ok();
  }
}
