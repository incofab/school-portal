<?php

namespace App\Http\Controllers\Institutions\Curriculums;

use Inertia\Inertia;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Models\ClassificationGroup;
use App\Http\Controllers\Controller;
use App\Models\CourseTeacher;
use App\Models\Institution;

class TopicController extends Controller
{
  //
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);

    // $this->allowedRoles([InstitutionUserType::Admin])->except(
    //   'index',
    //   'subTopicIndex',
    //   'show',
    //   'search'
    // );
  }

  //== Listing
  public function index(Institution $institution, ?Topic $topic = null)
  {
    $query = Topic::query()
      ->when(
        $topic,
        fn($q) => $q->where('parent_topic_id', $topic->id),
        fn($q) => $q->whereNull('parent_topic_id')
      )
      ->with('classificationGroup', 'course')
      ->latest('id');
    // dd(Topic::all()->toArray());
    // dd(json_encode(paginateFromRequest($query), JSON_PRETTY_PRINT) );
    return Inertia::render('institutions/topics/list-topics', [
      'parentTopic' => $topic,
      'topics' => paginateFromRequest($query),
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  /** @deprecated */
  public function subTopicIndex(Institution $institution, Topic $topic)
  {
    $query = Topic::where('parent_topic_id', $topic->id)->with(
      'classificationGroup',
      'course'
    );

    return Inertia::render('institutions/topics/list-sub-topics', [
      'parentTopic' => $topic->load('classificationGroup', 'course'),
      'subtopics' => paginateFromRequest($query->latest('id')),
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  //== Create/Edit Topic
  function createOrEdit(Institution $institution, ?Topic $topic = null)
  {
    $parentTopics = Topic::whereNull('parent_topic_id')->get();

    return Inertia::render('institutions/topics/create-edit-topic', [
      'parentTopics' => $parentTopics,
      'topic' => $topic?->load('classificationGroup', 'course')
    ]);
  }

  function show(Institution $institution, Topic $topic)
  {
    $topic->load('schemeOfWorks.lessonPlans.lessonNote');

    $institutionUser = currentInstitutionUser();
    $user = $institutionUser->user;

    //== Fetch all the courseTeacher ids of the teacher.
    if ($institutionUser->isTeacher()) {
      $assignedCourseIds = CourseTeacher::where('user_id', $user->id)
        ->pluck('id')
        ->toArray();
    } else {
      $assignedCourseIds = [];
    }

    //== For Parent Topics
    return Inertia::render('institutions/topics/show-topic', [
      'topic' => $topic,
      'assignedCourseIds' => $assignedCourseIds
    ]);
  }

  function search(Request $request, Institution $institution)
  {
    $query = Topic::query()->when(
      $request->search,
      fn($q, $value) => $q->where('title', 'LIKE', "%$value%")
    );
    return response()->json(['result' => $query->latest('id')->get()]);
  }

  function storeOrUpdate(
    Institution $institution,
    Request $request,
    ?Topic $topic = null
  ) {
    $data = $request->validate(Topic::createRule());

    $data = [
      ...collect($data)
        ->except('is_used_by_institution_group')
        ->toArray(),
      'institution_id' => $institution->id,
      'institution_group_id' => $data['is_used_by_institution_group']
        ? $institution->institutionGroup->id
        : null
    ];

    if (empty($topic)) {
      Topic::create($data);
    } else {
      $topic->update($data);
    }

    return $this->ok();
  }

  function destroy(Institution $institution, Topic $topic)
  {
    $hasSubTopics = Topic::where('parent_topic_id', $topic->id)->exists();
    $hasSchemeOfWork =
      $topic
        ->schemeOfWorks()
        ->get()
        ->count() > 0;

    if ($hasSubTopics || $hasSchemeOfWork) {
      return $this->message(
        'This Topic already has some Sub-Topics or Scheme of Work.',
        403
      );
    }

    $topic->delete();
    return $this->ok();
  }
}
