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
    LessonPlan $lessonPlan = null,
    LessonNote $lessonNote = null
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
    LessonNote $lessonNote = null
  ) {
    $data = $request->validate(LessonNote::createRule());

    $lessonPlanId = $lessonNote
      ? $lessonNote->lesson_plan_id
      : $data['lesson_plan_id'];
    $getLessonPlan = LessonPlan::where('id', $lessonPlanId)
      ->with('schemeOfWork.topic')
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

  function generateAiNote()
  {
    //$model = 'gemma-3-27b-it';
    $model = 'gemini-1.5-pro';

    $article = '<h1>Velocity</h1>

<h2>Introduction</h2>
<p>Velocity is a vector quantity that describes the rate at which an object changes its position.  It specifies both the speed and direction of motion.</p>

<h2>Difference between Speed and Velocity</h2>
<p>Speed is a scalar quantity that refers to "how fast an object is moving." Velocity refers to "the rate at which an object changes its position."  Speed is the magnitude of velocity.  An object can have a constant speed but changing velocity (e.g., moving in a circle).</p>

<h2>Units of Velocity</h2>
<p>The standard unit of velocity is meters per second (m/s). Other units include kilometers per hour (km/h), miles per hour (mph), and centimeters per second (cm/s). </p>

<h2>Types of Velocity</h2>

<h3>Uniform Velocity</h3>
<p>An object has uniform velocity if it covers equal displacements in equal intervals of time, however small the intervals may be, and moves along a straight line. This means the magnitude and direction of the velocity remain constant.</p>

<h3>Non-Uniform Velocity / Variable Velocity</h3>
<p>An object has non-uniform velocity if either its speed or direction of motion (or both) changes.  This implies that it covers unequal displacements in equal time intervals.</p>

<h3>Average Velocity</h3>
<p>Average velocity is the total displacement divided by the total time taken. It represents the overall rate of change of position over a period of time, regardless of variations in velocity during that time.  It is calculated as:</p>
<p>Average Velocity = Total Displacement / Total Time</p>

<h3>Instantaneous Velocity</h3>
<p>Instantaneous velocity is the velocity of an object at a particular instant in time.  It is the limit of the average velocity as the time interval approaches zero.</p>

Calculating Velocity
For uniform velocity:
Velocity (v) = Displacement (s) / Time (t)
For non-uniform velocity:
We usually calculate average velocity. However, using calculus (introduced in higher grades) can help determine instantaneous velocity.
Graphical Representation of Velocity
Displacement-Time Graph:
The slope of a displacement-time graph represents the velocity. A straight line indicates uniform velocity, while a curved line indicates non-uniform velocity. Steeper slopes indicate higher velocities.
Velocity-Time Graph
The slope of a velocity-time graph represents acceleration. The area under a velocity-time graph represents the displacement.
Relative Velocity
Relative velocity is the velocity of an object with respect to another object (which might itself be moving).  If two objects are moving in the same direction, the relative velocity is the difference between their velocities.  If they are moving in opposite directions, the relative velocity is the sum of their velocities.';

    // $question =
    //   'Using the Nigerian Basic Education Syllabus, write a class note for primary class 5 on the topic: ADDITION OF EVEN NUMBERS';

    // $question =
    //   'Using the Nigerian Basic Education Syllabus, write a class note for primary class 5 on the topic: SUBTRACTION OF EVEN NUMBERS. Format the response using html tags.';

    // $question =
    //   'Using the Nigerian Basic Education Syllabus, write a class note for middle grade / Senior Secondary class SSS 1 on the topic: VELOCITY. Format the response in markdown.';

    // $question =
    //   'Using the Nigerian Basic Education Syllabus, write a long detailed class note for middle grade / Senior Secondary class SSS 1 on the topic: VELOCITY. Try to touch every aspect of this topic in detail. Give me only the class note, no comment or side comment. You can include some practice questions. Return the response in pure html. Do not include stylings, meta tags, etc.';

    $question = "Generate 5 class test questions from the following article :: $article. Return the response in pure html. Do not include stylings, meta tags, etc.";

    $res = GoogleAiHelper::ask($question, $model);

    info($res);

    $res_parts = $res['candidates'][0]['content']['parts'];
    $full_note = '';

    foreach ($res_parts as $res_part) {
      $full_note .= $res_part['text'];
    }

    return str_replace('```html', '', $full_note);
  }
}
