<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassificationGroup;
use App\Models\Event;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except('index', 'show');
  }

  function index(Request $request, Institution $institution)
  {
    $student = currentInstitutionUser()
      ?->student()
      ->with('classification')
      ->first();

    $query = $institution
      ->events()
      ->getQuery()
      ->forStudent($student)
      ->withCount('eventCourseables');

    return Inertia::render('institutions/exams/list-events', [
      'events' => paginateFromRequest($query->latest('id')),
      'assessments' => Assessment::all()
    ]);
  }

  function create()
  {
    return Inertia::render('institutions/exams/create-edit-event', [
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function edit(Institution $institution, Event $event)
  {
    return Inertia::render('institutions/exams/create-edit-event', [
      'event' => $event,
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function show(Institution $institution, Event $event)
  {
    $event->load(
      'eventCourseables.courseable.course',
      'classificationGroup',
      'classification'
    );
    $student = currentInstitutionUser()->student;
    return Inertia::render('institutions/exams/show-event', [
      'event' => $event,
      'studentExam' => $student
        ? $event
        ->exams()
        ->getQuery()
        ->forExamable($student)
        ->first()
        : null
    ]);
  }

  function destroy(Institution $institution, Event $event)
  {
    $event->delete();
    return $this->ok();
  }

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate(Event::createRule());
    $institution->events()->create($data);
    return $this->ok();
  }

  function update(Request $request, Institution $institution, Event $event)
  {
    $data = $request->validate(Event::createRule($event));
    $event->fill($data)->save();
    return $this->ok();
  }
}