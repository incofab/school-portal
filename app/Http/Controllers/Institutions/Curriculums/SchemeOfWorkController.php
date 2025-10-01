<?php

namespace App\Http\Controllers\Institutions\Curriculums;

use Inertia\Inertia;
use App\Models\Topic;
use App\Models\Institution;
use App\Models\SchemeOfWork;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;

class SchemeOfWorkController extends Controller
{
  //
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->except('index');
    $this->allowedRoles([InstitutionUserType::Admin])->only('destroy');
  }

  function create(Institution $institution, Topic $topic)
  {
    return Inertia::render(
      'institutions/scheme-of-works/create-edit-scheme-of-work',
      [
        'topicId' => $topic->id
      ]
    );
  }

  function edit(Institution $institution, SchemeOfWork $schemeOfWork)
  {
    $parentTopics = Topic::whereNull('parent_topic_id')->get();

    return Inertia::render(
      'institutions/scheme-of-works/create-edit-scheme-of-work',
      [
        'parentTopics' => $parentTopics,
        'schemeOfWork' => $schemeOfWork->load('topic')
      ]
    );
  }

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate(SchemeOfWork::createRule());

    $params = [
      ...collect($data)
        ->except('is_used_by_institution_group')
        ->toArray(),
      'institution_id' => $institution->id,
      'institution_group_id' => $data['is_used_by_institution_group']
        ? $institution->institutionGroup->id
        : null
    ];

    SchemeOfWork::create($params);
    return $this->ok();
  }

  function update(
    Institution $institution,
    Request $request,
    SchemeOfWork $schemeOfWork
  ) {
    $data = $request->validate(SchemeOfWork::createRule());

    $schemeOfWork->update([
      'term' => $data['term'],
      'topic_id' => $data['topic_id'],
      'week_number' => $data['week_number'],
      'learning_objectives' => $data['learning_objectives'],
      'resources' => $data['resources'],
      'institution_id' => $institution->id,
      'institution_group_id' => $data['is_used_by_institution_group']
        ? $institution->institutionGroup->id
        : null
    ]);

    return $this->ok();
  }

  function destroy(Institution $institution, SchemeOfWork $schemeOfWork)
  {
    if (count($schemeOfWork->lessonPlans()->get()) > 0) {
      return $this->message(
        'This Scheme-of-Work already has a Lesson-Plan.',
        403
      );
    }

    $schemeOfWork->delete();
    return $this->ok();
  }
}
