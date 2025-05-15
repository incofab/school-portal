<?php

namespace App\Http\Controllers\Institutions\Curriculums;

use App\Enums\InstitutionUserType;
use Inertia\Inertia;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\NoteStatusType;
use App\Helpers\GoogleAiHelper;
use App\Models\CourseTeacher;
use App\Models\ClassificationGroup;
use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Support\UITableFilters\LessonNoteUITableFilters; 

class LessonNoteController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->except('index', 'show');
  }
  //

  public function index(Institution $institution, Request $request)
  {
    // dd("Heelll");
    $institutionUser = currentInstitutionUser();
    $user = $institutionUser->user;

    $query = LessonNote::query();
    $requestData = $request->all();

    if ($institutionUser->isStudent()) {
      $student = $institutionUser
        ->student()
        ->with('classification')
        ->first();
      $requestData['classification_id'] = $student->classification_id;
      $requestData['classification_group_id'] =
        $student->classification_group_id;

      $query->where('classification_id', $student->classification_id);
    } elseif ($institutionUser->isTeacher()) {
      $teacherCourses = CourseTeacher::where('user_id', $user->id)
        ->get(['course_id', 'classification_id'])
        ->map(function ($item) {
          return [$item->course_id, $item->classification_id];
        });

      $teacherCourseIds = $teacherCourses->pluck(0);
      $teacherClassIds = $teacherCourses->pluck(1);
      $requestData['classification_id'] = null;
      $requestData['classification_group_id'] = null;
      $requestData['course_id'] = null;

      $query
        ->whereIn('course_id', $teacherCourseIds)
        ->whereIn('classification_id', $teacherClassIds);
    }

    LessonNoteUITableFilters::make($requestData, $query)->filterQuery();

    return Inertia::render('institutions/lesson-notes/list-lesson-notes', [
      'lessonNotes' => paginateFromRequest(
        $query->with('classification', 'course')->latest('id')
      ),
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function createOrEdit(
    Institution $institution,
    ?LessonPlan $lessonPlan = null,
    ?LessonNote $lessonNote = null
  ) {
    //== Create New Lesson Note ==
    if (!empty($lessonPlan)) {
      //== A LessonPlan should have ONLY 1 LessonNote. Hence, check if a LessonNote already exist for this LessonPlan
      $hasLessonNote = LessonNote::where(
        'lesson_plan_id',
        $lessonPlan->id
      )->first();

      if ($hasLessonNote) {
        abort(401, 'A Lesson Note already exist for this Lesson Plan.');
      }

      $params['lessonPlan'] = $lessonPlan->load('schemeOfWork');
    }

    //== Edit Existing Lesson Note ==
    if (!empty($lessonNote)) {
      $params['lessonNote'] = $lessonNote;
    }

    return Inertia::render(
      'institutions/lesson-notes/create-edit-lesson-note',
      $params
    );
  }

  function storeOrUpdate(
    Institution $institution,
    Request $request,
    ?LessonNote $lessonNote = null
  ) {
    $data = $request->validate(LessonNote::createRule());

    $lessonPlanId = $lessonNote
      ? $lessonNote->lesson_plan_id
      : $data['lesson_plan_id'];
    
    $getLessonPlan = LessonPlan::where('id', $lessonPlanId)
      ->with('schemeOfWork.topic', 'courseTeacher')
      ->first();

    $classificationId = $getLessonPlan->courseTeacher->classification_id;
    $classificationGroupId =
      $getLessonPlan->schemeOfWork->topic->classification_group_id;
    $courseId = $getLessonPlan->schemeOfWork->topic->course_id;
    $term = $getLessonPlan->schemeOfWork->term;
    $topicId = $getLessonPlan->schemeOfWork->topic_id;
    $courseTeacherId = $getLessonPlan->course_teacher_id;

    $params = [
      'lesson_plan_id' => $lessonPlanId,
      'term' => $term,
      'title' => $data['title'],
      'content' => $data['content'],
      'course_id' => $courseId,
      'topic_id' => $topicId,
      'course_teacher_id' => $courseTeacherId,
      'status' => $data['is_published']
        ? NoteStatusType::Published
        : NoteStatusType::Draft,

      'institution_id' => $institution->id,
      'institution_group_id' => $data['is_used_by_institution_group']
        ? $institution->institutionGroup->id
        : null,
      'classification_id' => $classificationId,
      'classification_group_id' => $data['is_used_by_classification_group']
        ? $classificationGroupId
        : null
    ];

    if (empty($lessonNote)) {
      LessonNote::create($params);
    } else {
      $lessonNote->update($params);
    }

    return $this->ok();
  }

  function show(Institution $institution, LessonNote $lessonNote)
  {
    $institutionUser = currentInstitutionUser();

    if ($institutionUser->isStudent()) {
      $student = currentInstitutionUser()
        ->student()
        ->with('classification')
        ->first();

      if ($lessonNote->classification_id != $student->classification_id) {
        abort(403, 'You are not eligible for this Note.');
      }
    }

    return Inertia::render('institutions/lesson-notes/show-lesson-note', [
      'lessonNote' => $lessonNote->load('classification', 'course')
    ]);
  }

  function destroy(Institution $institution, LessonNote $lessonNote)
  {
    $institutionUser = currentInstitutionUser();

    if ($institutionUser->isTeacher()) {
      if ($institutionUser->user->id != $lessonNote->courseTeacher->user_id) {
        return $this->message(
          "Only a Note's Creator is allowed to delete the Note.",
          403
        );
      }
    }

    $lessonNote->delete();
    return $this->ok();
  }

  function generateAiNote(Request $request)
  {
    //$model = 'gemma-3-27b-it';
    //$model = 'gemini-1.5-pro';

    $getTopic = Topic::where('id', $request->topic_id)->first();

    if (empty($getTopic)) {
      $className = 'a class';
      $topicTitle = $request->title;
    } else {
      $className = $getTopic->classificationGroup->title;
      $topicTitle = $getTopic->title . ' - ' . $request->title;
    }

    $question = "Using the Nigerian Basic Education Syllabus, write a long detailed class note for $className on the topic: $topicTitle. Try to touch every aspect of this topic in detail. Give me only the class note, no comment or side comment. You can include some practice questions. Return the response in pure html. Do not include stylings, meta tags, etc.";

    $res = GoogleAiHelper::ask($question);

    $res_parts = $res['candidates'][0]['content']['parts'];
    $full_note = '';

    foreach ($res_parts as $res_part) {
      $full_note .= $res_part['text'];
    }

    $fullNote = str_replace('```html', '', $full_note);

    return $this->ok([$fullNote]);
  }
}
