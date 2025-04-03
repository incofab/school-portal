<?php

namespace App\Http\Controllers\Institutions\Curriculums;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use Illuminate\Http\Request;
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
  }

  function createOrEdit(
    Institution $institution,
    ?SchemeOfWork $schemeOfWork = null,
    ?LessonPlan $lessonPlan = null
  ) {
    $institutionUser = currentInstitutionUser();
    $user = $institutionUser->user;

    // Initialize $params
    $params = [];

    //== Edit Existing Lesson Plan ==
    if ($lessonPlan) {
      $schemeOfWork = $lessonPlan->schemeOfWork;
      $courseId = $lessonPlan->schemeOfWork->topic->course_id;
      $classificationIds = $lessonPlan->schemeOfWork->topic->classificationGroup
        ->classifications()
        ->pluck('id');

      // Pass the LessonPlan to the view
      $params['lessonPlan'] = $lessonPlan->load('courseTeacher.user');
    }

    //== Create New Lesson Plan ==
    if ($schemeOfWork) {
      $courseId = $schemeOfWork->topic->course_id;

      // dd($schemeOfWork->topic->classificationGroup()->get());
      $classificationIds = $schemeOfWork->topic->classificationGroup
        ->classifications()
        ->pluck('id');

      // Pass the SchemeOfWork to the view
      $params['schemeOfWork'] = $schemeOfWork;
    }

    //== Fetch the Teachers that teaches the subject for the class. ==
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

    //== Check if the teacher is allowed to create a LessonPlan for this subject/class. ==
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

  function storeOrUpdate(
    Institution $institution,
    Request $request,
    LessonPlan $lessonPlan = null
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
      LessonPlan::create($params);
    } else {
      $lessonPlan->update($params);
    }

    return $this->ok();
  }

  /* .. NO MORE IN USE ..
    function show(Institution $institution, LessonPlan $lessonPlan)
    {
        return Inertia::render('institutions/lesson-plans/show-lesson-plan', [
            'lessonPlan' => $lessonPlan->load('schemeOfWork.topic.course'),
        ]);
    }
    */

  function destroy(Institution $institution, LessonPlan $lessonPlan)
  {
    if (count($lessonPlan->lessonNote()->get()) > 0) {
      return $this->message('This Lesson-Plan already has a Lesson-Note.', 403);
    }

    $lessonPlan->delete();
    return $this->ok();
  }
}
