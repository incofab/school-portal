<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EventController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  function index(Request $request, Institution $institution)
  {
    $query = $institution->events()->getQuery();

    return Inertia::render('institutions/exams/list-events', [
      'events' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function create()
  {
    return Inertia::render('institutions/exams/create-edit-event', []);
  }

  function edit(Institution $institution, Event $event)
  {
    return Inertia::render('institutions/exams/create-edit-event', [
      'event' => $event
    ]);
  }

  function destroy(Institution $institution, Event $event)
  {
    $event->delete();
    return $this->ok();
  }

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        Rule::unique('events', 'id')->where('institution_id', $institution->id)
      ],
      'description' => ['nullable', 'string'],
      'duration' => ['required', 'numeric'],
      'starts_at' => ['required', 'date'],
      'num_of_subjects' => ['required', 'integer'],
      'num_of_activations' => ['nullable', 'integer']
    ]);
    $institution->events()->create($data);
    return $this->ok();
  }

  function update(Request $request, Institution $institution, Event $event)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        Rule::unique('events', 'id')
          ->where('institution_id', $institution->id)
          ->ignore($event->id, 'id')
      ],
      'description' => ['nullable', 'string'],
      'duration' => ['required', 'numeric'],
      'starts_at' => ['required', 'date'],
      'num_of_subjects' => ['nullable', 'integer'],
      'num_of_activations' => ['nullable', 'integer']
    ]);
    $event->fill($data)->save();
    return $this->ok();
  }
}
