<?php

namespace App\Http\Controllers\Institutions\Assignments;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\Audit\ModelAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

  public function index(Request $request, Institution $institution)
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
  public function list(Institution $institution, Assignment $assignment)
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

  public function show(
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

  public function store(Institution $institution, Request $request)
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

    $submission = ModelAudit::withoutAuditingFor(
      AssignmentSubmission::class,
      fn() => AssignmentSubmission::create([
        ...$data,
        'student_id' => $student->id
      ])
    );
    app(AcademicActivityLogger::class)->assignmentSubmitted(
      $institution,
      $submission
    );

    return $this->ok();
  }

  public function score(
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

    $oldScore = $assignmentSubmission->score;
    ModelAudit::withoutAuditingFor(
      AssignmentSubmission::class,
      function () use ($assignmentSubmission, $request) {
        $assignmentSubmission->update([
          'score' => $request->score,
          'remark' => $request->remark
        ]);
      }
    );
    app(AcademicActivityLogger::class)->assignmentScored(
      $institution,
      $assignmentSubmission,
      $oldScore,
      $request->score
    );

    return $this->ok();
  }

  public function isAssignmentCourseTeacher($user, $assignment)
  {
    $classificationIds = $assignment->classification_ids;

    if (empty($classificationIds)) {
      $classificationIds = $assignment
        ->classifications()
        ->pluck('classifications.id')
        ->all();
    }

    $courseTeacher = CourseTeacher::where('user_id', $user->id)
      ->where('course_id', $assignment->course_id)
      ->whereIn('classification_id', $classificationIds)
      ->first();

    if (!$courseTeacher) {
      return false;
    }

    return true;
  }
}
