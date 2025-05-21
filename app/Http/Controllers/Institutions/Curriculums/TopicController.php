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
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;

class TopicController extends Controller
{
  //
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
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
    // $data = $request->validate(Topic::createRule());

    //= Check if there's a topic and use the corresponding validation rule
    $data = $request->validate(
      $topic ? Topic::createRule2() : Topic::createRule()
    );

    //= For Topic
    $params_topic = [
      ...collect($data)
        ->except(
          'is_used_by_institution_group',
          'term',
          'week_number',
          'user_id'
        )
        ->toArray(),
      'institution_id' => $institution->id,
      'institution_group_id' => $data['is_used_by_institution_group']
        ? $institution->institutionGroup->id
        : null
    ];

    //== CREATE NEW RECORDS
    if (empty($topic)) {
      //= For SchemeOfWork
      $params_scheme_of_work = [
        'term' => $data['term'],
        'week_number' => $data['week_number'],
        'learning_objectives' => 'NA',
        'resources' => 'NA',
        'institution_id' => $institution->id,
        'institution_group_id' => $data['is_used_by_institution_group']
          ? $institution->institutionGroup->id
          : null
      ];

      //= For LessonPlan
      $getCourseTeacher = $this->getCourseTeacher($data);

      if (!$getCourseTeacher) {
        return $this->message(
          'This teacher is not assigned to this class subject.',
          401
        );
      }

      $params_lesson_plan = [
        'course_teacher_id' => $getCourseTeacher->id,
        'objective' => 'NA',
        'activities' => 'NA',
        'content' => 'NA',
        'institution_id' => $institution->id,
        'institution_group_id' => $data['is_used_by_institution_group']
          ? $institution->institutionGroup->id
          : null
      ];

      //== Create Topic
      $newTopic = Topic::create($params_topic);

      //== Create Scheme_Of_Work
      $newSchemeOfWork = SchemeOfWork::create([
        ...$params_scheme_of_work,
        'topic_id' => $newTopic->id
      ]);

      //== Create LessonPlan
      LessonPlan::create([
        ...$params_lesson_plan,
        'scheme_of_work_id' => $newSchemeOfWork->id
      ]);
    }

    //== UPDATE EXISTING RECORDS
    if (!empty($topic)) {
      //= Update $topic only.
      $topic->update($params_topic);
    }

    return $this->ok();
  }

  function getCourseTeacher($data)
  {
    $institutionUser = currentInstitutionUser();
    $user = $institutionUser->user;

    if ($institutionUser->isTeacher()) {
      $userId = $user->id;
    }

    if ($institutionUser->isAdmin()) {
      $userId = $data['user_id'];
    }

    $reqClassGroupId = $data['classification_group_id'];

    if (is_int($reqClassGroupId)) {
      $getClassGroup = ClassificationGroup::find($reqClassGroupId);
    } else {
      // Parent Topic was selected, hence $reqClassGroup is not an integer.
      $reqParentTopicId = $data['parent_topic_id'];
      $getParentTopic = Topic::find($reqParentTopicId);
      $getClassGroup = ClassificationGroup::find(
        $getParentTopic->classification_group_id
      );
    }

    $classGroup_classification_ids = $getClassGroup
      ->classifications()
      ->pluck('id')
      ->toArray();

    $courseTeacher = CourseTeacher::where('course_id', $data['course_id'])
      ->where('user_id', $userId)
      ->whereIn('classification_id', $classGroup_classification_ids)
      ->first();

    return $courseTeacher;
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
