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
    $this->allowedRoles([InstitutionUserType::Admin])->except('index');
  }

  //== Listing
  /* .. NO MORE IN USE ..
    public function index(Institution $institution)
    {
        $institutionUser = currentInstitutionUser();
        $user = $institutionUser->user;

        $query = SchemeOfWork::query();

        if ($institutionUser->isTeacher()) {
            $teacherCourses = CourseTeacher::where('user_id', $user->id)
                ->with('classification.classificationGroup') // Load related classification and classification group
                ->get();

            // Apply filters in the query
            $query->whereHas('topic', function ($query) use ($teacherCourses) {
                $query->where(function ($query) use ($teacherCourses) {
                    foreach ($teacherCourses as $teacherCourse) {
                        $query->orWhere(function ($query) use ($teacherCourse) {
                            $query->where('course_id', $teacherCourse->course_id)
                                ->where('classification_group_id', $teacherCourse->classification->classificationGroup->id ?? null);
                        });
                    }
                });
            });
        }

        return Inertia::render('institutions/scheme-of-works/list-scheme-of-works', [
            'schemeOfWorks' => paginateFromRequest($query->with('lessonPlan', 'topic.classificationGroup', 'topic.course')->latest('id')),
            'classificationGroups' => ClassificationGroup::all()
        ]);
    }
  */

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

  /*
    function storeOrUpdate(Institution $institution, Request $request, SchemeOfWork $schemeOfWork = null)
    {
        $data = $request->validate(SchemeOfWork::createRule());

        $params = [
            'term' => $data['term'],
            'topic_id' => $data['topic_id'],
            'week_number' => $data['week_number'],
            'learning_objectives' => $data['learning_objectives'],
            'resources' => $data['resources'],
            'institution_id' => $institution->id,
            'institution_group_id' => $data['is_used_by_institution_group'] ? $institution->institutionGroup->id : null,
        ];

        if (empty($schemeOfWork)) {
            SchemeOfWork::create($params);
        } else {
            $schemeOfWork->update($params);
        }

        return $this->ok();
    }
  */

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate(SchemeOfWork::createRule());

    SchemeOfWork::create([
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

  /* .. NO MORE IN USE ..
    function show(Institution $institution, SchemeOfWork $schemeOfWork)
    {
        return Inertia::render('institutions/scheme-of-works/show-scheme-of-work', [
            'schemeOfWork' => $schemeOfWork->load('topic'),
        ]);
    }
  */

  function destroy(Institution $institution, SchemeOfWork $schemeOfWork)
  {
    if (!empty($schemeOfWork->lessonPlans()->get())) {
      return $this->message(
        'This Scheme-of-Work already has a Lesson-Plan.',
        403
      );
    }

    $schemeOfWork->delete();
    return $this->ok();
  }
}