<?php

namespace App\Http\Controllers\Institutions\Curriculums;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Curriculums\TopicRequest;
use App\Models\ClassificationGroup;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use App\Models\Topic;
use App\Services\Curriculum\CurriculumMediaService;
use App\Support\UITableFilters\TopicUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TopicController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
    $this->allowedRoles([InstitutionUserType::Admin])->only('destroy');
  }

  public function index(Institution $institution, ?Topic $topic = null)
  {
    $query = Topic::query()->when(
      $topic,
      fn($q) => $q->where('parent_topic_id', $topic->id),
      fn($q) => $q->whereNull('parent_topic_id')
    );
    TopicUITableFilters::make(request()->all(), $query)
      ->filterQuery()
      ->getQuery()
      ->with('classificationGroup', 'course')
      ->latest('id');
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

  function createOrEdit(Institution $institution, ?Topic $topic = null)
  {
    $parentTopics = Topic::query()
      ->whereNull('parent_topic_id')
      ->with('classificationGroup', 'course')
      ->latest('id')
      ->get();

    return Inertia::render('institutions/topics/create-edit-topic', [
      'parentTopics' => $parentTopics,
      'topic' => $topic?->load(
        'classificationGroup',
        'course',
        'parentTopic',
        'schemeOfWorks.lessonPlans.media'
      )
    ]);
  }

  function show(Institution $institution, Topic $topic)
  {
    $topic->load([
      'classificationGroup',
      'course',
      'parentTopic',
      'schemeOfWorks' => fn($query) => $query
        ->with([
          'lessonPlans' => fn($lessonPlanQuery) => $lessonPlanQuery->with([
            'courseTeacher.user',
            'courseTeacher.classification',
            'media',
            'lessonNote.media'
          ])
        ])
        ->orderBy('term')
        ->orderBy('week_number')
    ]);

    $institutionUser = currentInstitutionUser();
    $user = $institutionUser->user;

    if ($institutionUser->isTeacher()) {
      $assignedCourseIds = CourseTeacher::query()
        ->where('user_id', $user->id)
        ->pluck('id')
        ->toArray();
    } else {
      $assignedCourseIds = [];
    }

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

  public function storeOrUpdate(
    Institution $institution,
    TopicRequest $request,
    CurriculumMediaService $curriculumMediaService,
    ?Topic $topic = null
  ) {
    $data = $request->validated();

    $topicAttributes = [
      ...collect($data)
        ->except(
          'is_used_by_institution_group',
          'term',
          'week_number',
          'user_id',
          'lesson_plan_files'
        )
        ->toArray(),
      'institution_id' => $institution->id,
      'institution_group_id' => $data['is_used_by_institution_group']
        ? $institution->institutionGroup->id
        : null
    ];

    if ($topic) {
      $topic->update($topicAttributes);
      $this->uploadTopicLessonPlanFiles(
        $institution,
        $topic,
        $request->file('lesson_plan_files', []),
        $curriculumMediaService
      );

      return $this->ok();
    }

    $courseTeacher = $this->getCourseTeacher($data);

    if (!$courseTeacher) {
      return $this->message(
        'This teacher is not assigned to this class subject.',
        401
      );
    }

    DB::transaction(function () use (
      $institution,
      $data,
      $topicAttributes,
      $courseTeacher,
      $request,
      $curriculumMediaService
    ) {
      $newTopic = Topic::query()->create($topicAttributes);
      $newSchemeOfWork = SchemeOfWork::query()->create([
        'term' => $data['term'],
        'week_number' => $data['week_number'],
        'learning_objectives' => 'NA',
        'resources' => 'NA',
        'institution_id' => $institution->id,
        'institution_group_id' => $data['is_used_by_institution_group']
          ? $institution->institutionGroup->id
          : null,
        'topic_id' => $newTopic->id
      ]);
      $newLessonPlan = LessonPlan::query()->create([
        'course_teacher_id' => $courseTeacher->id,
        'objective' => 'NA',
        'activities' => 'NA',
        'content' => 'NA',
        'institution_id' => $institution->id,
        'institution_group_id' => $data['is_used_by_institution_group']
          ? $institution->institutionGroup->id
          : null,
        'scheme_of_work_id' => $newSchemeOfWork->id
      ]);

      foreach ($request->file('lesson_plan_files', []) as $file) {
        $curriculumMediaService->storeLessonPlanAttachment(
          $institution,
          $newLessonPlan,
          $file
        );
      }
    });

    return $this->ok();
  }

  private function getCourseTeacher(array $data): ?CourseTeacher
  {
    $institutionUser = currentInstitutionUser();
    $user = $institutionUser->user;
    $userId = null;

    if ($institutionUser->isTeacher()) {
      $userId = $user->id;
    }

    if ($institutionUser->isAdmin()) {
      $userId = $data['user_id'];
    }

    $reqClassGroupId = $data['classification_group_id'];

    $getClassGroup = ClassificationGroup::query()->find($reqClassGroupId);

    if (!$getClassGroup && !empty($data['parent_topic_id'])) {
      $getClassGroup = Topic::query()->find($data['parent_topic_id'])
        ?->classificationGroup;
    }

    if (!$getClassGroup || !$userId) {
      return null;
    }

    $classGroup_classification_ids = $getClassGroup
      ->classifications()
      ->pluck('id')
      ->toArray();

    return CourseTeacher::query()
      ->where('course_id', $data['course_id'])
      ->where('user_id', $userId)
      ->whereIn('classification_id', $classGroup_classification_ids)
      ->first();
  }

  private function uploadTopicLessonPlanFiles(
    Institution $institution,
    Topic $topic,
    array $files,
    CurriculumMediaService $curriculumMediaService
  ): void {
    if (empty($files)) {
      return;
    }

    $lessonPlan = $topic
      ->schemeOfWorks()
      ->with('lessonPlans')
      ->oldest('id')
      ->get()
      ->pluck('lessonPlans')
      ->flatten()
      ->first();

    if (!$lessonPlan) {
      return;
    }

    foreach ($files as $file) {
      $curriculumMediaService->storeLessonPlanAttachment(
        $institution,
        $lessonPlan,
        $file
      );
    }
  }

  function destroy(Institution $institution, Topic $topic)
  {
    $hasSubTopics = $topic->subTopics()->exists();
    $hasSchemeOfWork = $topic->schemeOfWorks()->exists();

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
