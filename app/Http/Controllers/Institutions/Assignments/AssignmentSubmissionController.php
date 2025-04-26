<?php

namespace App\Http\Controllers\Institutions\Assignments;

use Inertia\Inertia;
use App\Models\Assignment;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\CourseTeacher;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;

class AssignmentSubmissionController extends Controller
{
  //
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->except('index', 'show', 'store');
  }

  function index(Request $request, Institution $institution)
  {
    $user = currentInstitutionUser();

    if ($user->isStudent()) {
      $student = $user->student;

      $assignmentSubmissions = AssignmentSubmission::where(
        'student_id',
        $student->id
      )
        ->with(['assignment.course', 'assignment.classification'])
        ->with('student.user');
    } else {
      abort(401, 'Unauthorized');
    }

    return Inertia::render(
      'institutions/assignments/list-assignment-submissions',
      [
        'assignmentSubmissions' => paginateFromRequest(
          $assignmentSubmissions->latest('id')
        )
      ]
    );
  }

  /**
   * This function shows a list of all assignmentSubmissions for a particular/given Assignment
   */
  function list(Institution $institution, Assignment $assignment)
  {
    $user = currentUser();
    $institutionUser = currentInstitutionUser();

    if ($institutionUser->isTeacher()) {
      $assgnmentCourseTeacher = $this->isAssignmentCourseTeacher(
        $user,
        $assignment
      );
      abort_unless($assgnmentCourseTeacher, 401, 'Unauthorized Operation');
    }

    $assignmentSubmissions = AssignmentSubmission::where(
      'assignment_id',
      $assignment->id
    )
      ->with(['assignment.course', 'assignment.classification'])
      ->with('student.user');

    return Inertia::render(
      'institutions/assignments/list-assignment-submissions',
      [
        'assignmentSubmissions' => paginateFromRequest(
          $assignmentSubmissions->latest('id')
        )
      ]
    );
  }

  function show(
    Institution $institution,
    AssignmentSubmission $assignmentSubmission
  ) {
    $user = currentUser();

    if ($user->isInstitutionStudent()) {
      $student = currentInstitutionUser()->student;

      if ($assignmentSubmission->student_id != $student->id) {
        abort(401, 'Unauthorized Operation.');
      }
    } elseif ($user->isInstitutionTeacher()) {
      $assignment = $assignmentSubmission->assignment;
      $assignmentCourseTeacher = $this->isAssignmentCourseTeacher(
        $user,
        $assignment
      );

      if (!$assignmentCourseTeacher) {
        abort(401, 'Unauthorized Operation.');
      }
    } elseif ($user->isInstitutionAdmin()) {
    } else {
      abort(401, 'Unauthorized');
    }

    return Inertia::render(
      'institutions/assignments/show-assignment-submission',
      [
        'assignmentSubmission' => $assignmentSubmission->load('assignment')
      ]
    );
  }

  function store(Institution $institution, Request $request)
  {
    $user = currentInstitutionUser();
    if (!$user->isStudent()) {
      abort(401, 'Unauthorized Operation');
    }

    $data = $request->validate([
      'assignment_id' => 'required|integer',
      'answer' => 'required|string'
    ]);

    $student = $user->student;

    AssignmentSubmission::create([...$data, 'student_id' => $student->id]);
    return $this->ok();
  }

  function score(
    Institution $institution,
    AssignmentSubmission $assignmentSubmission,
    Request $request
  ) {
    $user = currentUser();

    if ($user->isInstitutionTeacher()) {
      $assignment = $assignmentSubmission->assignment;
      $assignmentCourseTeacher = $this->isAssignmentCourseTeacher(
        $user,
        $assignment
      );

      if (!$assignmentCourseTeacher) {
        abort(401, 'Unauthorized Operation.');
      }
    }

    $maxScore = $assignmentSubmission->assignment->max_score;

    $request->validate([
      'score' => 'required|integer|min:0|max:' . $maxScore,
      'remark' => 'nullable|string'
    ]);

    $assignmentSubmission->update([
      'score' => $request->score,
      'remark' => $request->remark
    ]);

    return $this->ok();
  }

  function isAssignmentCourseTeacher($user, $assignment)
  {
    $courseTeacher = CourseTeacher::where('user_id', $user->id)
      ->where('course_id', $assignment->course_id)
      ->whereIn('classification_id', $assignment->classification_ids ?? [])
      ->first();

    if (!$courseTeacher) {
      return false;
    }

    return true;
  }
}
