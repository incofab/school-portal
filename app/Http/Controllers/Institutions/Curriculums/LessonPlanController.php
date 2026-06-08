<?php

namespace App\Http\Controllers\Institutions\Curriculums;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\InstitutionUserType;
use App\Enums\Media\MediaVisibility;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\LessonPlan;
use App\Models\Media;
use App\Models\SchemeOfWork;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\Media\MediaManager;
use App\Support\UITableFilters\LessonPlanUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LessonPlanController extends Controller
{
  //
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
    $this->allowedRoles([InstitutionUserType::Admin])->only('destroy');
  }

  public function index(Institution $institution, Request $request)
  {
    $institutionUser = currentInstitutionUser();
    $query = LessonPlan::query();

    if ($institutionUser->isTeacher()) {
      $query->whereIn(
        'course_teacher_id',
        $institutionUser->user->courseTeachers()->pluck('id')
      );
    }

    LessonPlanUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/lesson-plans/list-lesson-plans', [
      'lessonPlans' => paginateFromRequest(
        $query
          ->with(
            'schemeOfWork.topic.classificationGroup',
            'schemeOfWork.topic.course',
            'courseTeacher.classification',
            'courseTeacher.user',
            'lessonNote'
          )
          ->latest('id')
      )
    ]);
  }

  public function createOrEdit(
    Institution $institution,
    ?SchemeOfWork $schemeOfWork = null,
    ?LessonPlan $lessonPlan = null
  ) {
    $institutionUser = currentInstitutionUser();
    $user = $institutionUser->user;

    // Initialize $params
    $params = [];

    // == Edit Existing Lesson Plan ==
    if ($lessonPlan) {
      $schemeOfWork = $lessonPlan->schemeOfWork;
      $courseId = $lessonPlan->schemeOfWork->topic->course_id;
      $classificationIds = $lessonPlan->schemeOfWork->topic->classificationGroup
        ->classifications()
        ->pluck('id');

      // Pass the LessonPlan to the view
      $params['lessonPlan'] = $lessonPlan->load('courseTeacher.user', 'media');
    }

    // == Create New Lesson Plan ==
    if ($schemeOfWork) {
      $courseId = $schemeOfWork->topic->course_id;

      // dd($schemeOfWork->topic->classificationGroup()->get());
      $classificationIds = $schemeOfWork->topic->classificationGroup
        ->classifications()
        ->pluck('id');

      // Pass the SchemeOfWork to the view
      $params['schemeOfWork'] = $schemeOfWork;
    }

    // == Fetch the Teachers that teaches the subject for the class. ==
    if ($institutionUser->isTeacher()) {
      $query = CourseTeacher::where('user_id', $user->id);
    }
    if ($institutionUser->isAdmin()) {
      $query = CourseTeacher::query();
    }

    $lessonPlanCourseTeachers = $query
      ->where('course_id', $courseId)
      ->whereIn('classification_id', $classificationIds)
      ->with('user', 'classification')
      ->get();

    // == Check if the teacher is allowed to create a LessonPlan for this subject/class. ==
    if ($institutionUser->isTeacher() && $lessonPlanCourseTeachers->isEmpty()) {
      abort(
        401,
        "Only a subject's teacher is allowed to create a Lesson Plan for the subject."
      );
    }

    $params['lessonPlanCourseTeachers'] = $lessonPlanCourseTeachers;

    return Inertia::render(
      'institutions/lesson-plans/create-edit-lesson-plan',
      $params
    );
  }

  public function storeOrUpdate(
    Institution $institution,
    Request $request,
    ?LessonPlan $lessonPlan = null
  ) {
    $data = $request->validate(LessonPlan::createRule());

    $params = collect($data)
      ->only(['course_teacher_id', 'objective', 'activities', 'content'])
      ->merge([
        'scheme_of_work_id' => $lessonPlan
          ? $lessonPlan->scheme_of_work_id
          : $data['scheme_of_work_id'],
        'institution_id' => $institution->id,
        'institution_group_id' => $data['is_used_by_institution_group']
          ? $institution->institutionGroup->id
          : null
      ])
      ->toArray();

    if (empty($lessonPlan)) {
      $lessonPlan = LessonPlan::create($params);
    } else {
      $lessonPlan->update($params);
    }

    return $this->ok();
  }

  public function uploadMedia(
    Institution $institution,
    Request $request,
    LessonPlan $lessonPlan
  ) {
    $data = $request->validate([
      'file' => [
        'required',
        'file',
        'mimes:jpg,jpeg,png,webp,pdf,doc,docx,mp4,mov,avi,mkv,mp3,wav',
        'max:10240'
      ]
    ]);

    $res = app(MediaManager::class)->storeUploadedFile(
      $data['file'],
      $lessonPlan,
      'attachments',
      $institution->folder(S3Folder::LessonPlans, (string) $lessonPlan->id),
      $institution,
      currentUser(),
      visibility: MediaVisibility::Public
    );

    app(AcademicActivityLogger::class)->workflowEvent(
      $institution,
      'curriculum.lesson_plan_attachment_uploaded',
      ActivityLogCategory::Curriculum,
      'uploaded_attachment',
      'Lesson plan attachment uploaded.',
      [
        'lesson_plan_id' => $lessonPlan->id,
        'media_id' => $res->media->id,
        'original_name' => $res->media->original_name,
        'collection_name' => $res->media->collection_name,
        'mime_type' => $res->media->mime_type,
        'size' => $res->media->size
      ],
      $lessonPlan
    );

    return $this->ok(['media' => $res->media]);
  }

  public function destroyMedia(
    Institution $institution,
    LessonPlan $lessonPlan,
    Media $media
  ) {
    abort_unless(
      $media->mediable_type === $lessonPlan->getMorphClass() &&
        $media->mediable_id === $lessonPlan->id &&
        $media->collection_name === 'attachments',
      404
    );

    $properties = [
      'lesson_plan_id' => $lessonPlan->id,
      'media_id' => $media->id,
      'original_name' => $media->original_name,
      'collection_name' => $media->collection_name,
      'mime_type' => $media->mime_type,
      'size' => $media->size
    ];

    Storage::disk($media->disk)->delete($media->path);
    $media->delete();

    app(AcademicActivityLogger::class)->workflowEvent(
      $institution,
      'curriculum.lesson_plan_attachment_deleted',
      ActivityLogCategory::Curriculum,
      'deleted_attachment',
      'Lesson plan attachment deleted.',
      $properties,
      $lessonPlan
    );

    return $this->ok();
  }

  public function show(Institution $institution, LessonPlan $lessonPlan)
  {
    $lessonPlan
      ->load(
        'schemeOfWork.topic.course',
        'schemeOfWork.topic.classification',
        'schemeOfWork.topic.parentTopic',
        'courseTeacher',
        'media'
      )
      ->loadCount('lessonNotes');

    return Inertia::render('institutions/lesson-plans/show-lesson-plan', [
      'lessonPlan' => $lessonPlan
    ]);
  }

  public function destroy(Institution $institution, LessonPlan $lessonPlan)
  {
    if (count($lessonPlan->lessonNote()->get()) > 0) {
      return $this->message('This Lesson-Plan already has a Lesson-Note.', 403);
    }

    $lessonPlan->delete();

    return $this->ok();
  }
}
