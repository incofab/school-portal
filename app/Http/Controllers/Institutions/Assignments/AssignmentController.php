<?php

namespace App\Http\Controllers\Institutions\Assignments;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Assignment;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\CourseTeacher;
use App\Support\SettingsHandler;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;

class AssignmentController extends Controller
{
  //
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->except('index', 'show');
  }

  function index(Request $request, Institution $institution)
  {
    $user = currentInstitutionUser();

    if ($user->isStudent()) {
      $student = $user->student()->first();

      $submittedAssignments = AssignmentSubmission::where(
        'student_id',
        $student->id
      )->pluck('assignment_id');

      $assignments = Assignment::where(
        'classification_id',
        $student->classification_id
      )
        ->notExpired()
        ->whereNotIn('id', $submittedAssignments)
        ->with('course')
        ->with('classification');
    } elseif ($user->isTeacher()) {
      $teacherCourses = CourseTeacher::where('user_id', $user->user->id)->pluck(
        'course_id'
      );

      $assignments = Assignment::whereIn('course_id', $teacherCourses)
        ->with('course')
        ->with('classification');
    } elseif ($user->isAdmin()) {
      $assignments = Assignment::with('course')->with('classification');
    } else {
      abort(401, 'Unauthorized');
    }

    return Inertia::render('institutions/assignments/list-assignments', [
      'assignments' => paginateFromRequest($assignments->latest('id'))
    ]);
  }

  function create()
  {
    $user = currentUser();
    $teacherCourses = [];
    if (!$user->isInstitutionAdmin()) {
      $teacherCourses = CourseTeacher::where('user_id', $user->id)
        ->with('course', 'classification')
        ->get();
    }

    return Inertia::render('institutions/assignments/create-edit-assignment', [
      'teacherCourses' => $teacherCourses
    ]);
  }

  function edit(Institution $institution, Assignment $assignment)
  {
    $user = currentUser();

    $teacherCourses = [];
    if (!$user->isInstitutionAdmin()) {
      $teacherCourses = CourseTeacher::where('user_id', $user->id)
        ->with('course', 'classification')
        ->get();
    }

    // dd($assignment->load('courseTeacher.user', 'course', 'classification')->toArray());

    return Inertia::render('institutions/assignments/create-edit-assignment', [
      'assignment' => $assignment->load(
        'courseTeacher.user',
        'course',
        'classification'
      ),
      'teacherCourses' => $teacherCourses
    ]);
  }

  function show(Institution $institution, Assignment $assignment)
  {
    $user = currentInstitutionUser();

    if ($user->isStudent()) {
      $currentTime = Carbon::now();
      $student = currentInstitutionUser()
        ->student()
        ->with('classification')
        ->first();
      $submittedAssignments = AssignmentSubmission::where(
        'student_id',
        $student->id
      )->pluck('assignment_id');

      if ($assignment->classification_id != $student->classification_id) {
        abort(403, 'You are not eligible for this assignment.');
      }

      if ($currentTime > $assignment->expires_at) {
        abort(403, 'Submission Deadline has passed.');
      }

      if ($submittedAssignments->contains($assignment->id)) {
        abort(403, 'You have already submitted this assignment.');
      }
    } elseif ($user->isTeacher()) {
      $course_teacher_user_id = $assignment->courseTeacher->user_id;
      $current_user_id = $user->user->id;

      if ($course_teacher_user_id != $current_user_id) {
        abort(401, 'Unauthorized.');
      }
    } elseif ($user->isAdmin()) {
    } else {
      abort(401, 'Unauthorized');
    }

    return Inertia::render('institutions/assignments/show-assignment', [
      'assignment' => $assignment->load('course')
      // 'course' => $assignment->course,
    ]);
  }

  function destroy(Institution $institution, Assignment $assignment)
  {
    abort_if(
      $assignment->assignmentSubmissions()->exists(),
      403,
      'Assignment already has some submissions.'
    );

    $user = currentUser();
    $institutionUser = currentInstitutionUser();

    if ($institutionUser->isTeacher()) {
      $assignmentCourseTeacher = $assignment
        ->courseTeacher()
        ->where('user_id', $user->id)
        ->first();
      abort_unless(
        $assignmentCourseTeacher,
        401,
        'Only the Course Teacher is allowed to delete an Assignment.'
      );
    }

    $assignment->delete();
    return $this->ok();
  }

  function store(Institution $institution, Request $request)
  {
    $user = currentUser();
    $data = $request->validate(Assignment::createRule());
    // == Grab 'courseId' and 'classificationId'
    $courseTeacher = CourseTeacher::find($request->course_teacher_id);

    // == Check if user is an admin or the specific course teacher assigned to this assignment
    if (!$user->isInstitutionAdmin() && $courseTeacher->user_id !== $user->id) {
      abort(403, 'You can only ');
    }

    $settingsHandler = SettingsHandler::makeFromRoute();
    // == Create Record.
    $institution
      ->assignments()
      ->create([
        ...$data,
        'course_id' => $courseTeacher->course_id,
        'classification_id' => $courseTeacher->classification_id,
        'academic_session_id' => $settingsHandler->getCurrentAcademicSession(),
        'term' => $settingsHandler->getCurrentTerm()
      ]);
    return $this->ok();
  }

  function update(
    Request $request,
    Institution $institution,
    Assignment $assignment
  ) {
    $user = currentUser();
    $data = $request->validate(Assignment::createRule());

    // == Grab 'courseId' and 'classificationId' - incase the assignment's Subject was changed
    $courseTeacher = CourseTeacher::query()->find($request->course_teacher_id);

    // == Check if user is an admin or the specific course teacher assigned to this assignment
    if (!$user->isInstitutionAdmin() && $courseTeacher->user_id !== $user->id) {
      abort(403, 'Forbidden');
    }

    $assignment->update([
      ...$data,
      'course_id' => $courseTeacher->course_id,
      'classification_id' => $courseTeacher->classification_id
    ]);
    return $this->ok();
  }
}
