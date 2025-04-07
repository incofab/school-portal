<?php

namespace App\Http\Controllers\Institutions\Assignments;

use App\Actions\RecordAssignment;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Assignment;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\CourseTeacher;
use App\Support\SettingsHandler;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentRequest;
use App\Models\AssignmentSubmission;
use App\Models\Classification;
use App\Models\InstitutionUser;
use App\Support\Queries\AssignmentQueryBuilder;
use App\Support\UITableFilters\AssignmentUITableFilters;

class AssignmentController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->except('index', 'show');

    $this->allowedRoles([
      InstitutionUserType::Student,
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->only('index', 'show');
  }

  function index(Request $request, Institution $institution)
  {
    $institutionUser = currentInstitutionUser();

    $query = Assignment::query()
    ->init()
    ->when(
      $institutionUser->isStudent(),
      fn(AssignmentQueryBuilder $q) => $q->forStudent($institutionUser->student()->firstOrFail())
    )
    ->when(
      $institutionUser->isTeacher(),
      fn($q) => $q->forTeacher($institutionUser)
    )->with('course', 'classifications');

    AssignmentUITableFilters::make($request->all(), $query)->filterQuery();
    
    return Inertia::render('institutions/assignments/list-assignments', [
      'assignments' => paginateFromRequest($query->latest('assignments.id'))
    ]);
  }

  function create()
  {
    return Inertia::render('institutions/assignments/create-edit-assignment');
  }

  function edit(Institution $institution, Assignment $assignment)
  {
    $assignment->load('classifications');

    return Inertia::render('institutions/assignments/create-edit-assignment', [
      'assignment' => $assignment,
    ]);
  }

  function show(Institution $institution, Assignment $assignment)
  {
<<<<<<< HEAD
    $this->authorize('view', $assignment);
    $institutionUser = currentInstitutionUser();
    
    abort_if(
      $institutionUser->isStudent() && now() > $assignment->expires_at,
      403,
      'Submission Deadline has passed.'
    );
=======
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
      )
        ->with('classification')
        ->pluck('assignment_id');

      if (
        $assignment->classification->classification_group_id !=
        $student->classification->classification_group_id
      ) {
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
>>>>>>> e42b5313d399e660cf1d35680def02e598a8171d

    return Inertia::render('institutions/assignments/show-assignment', [
      'assignment' => $assignment->load('course')
    ]);
  }

  function destroy(Institution $institution, Assignment $assignment)
  {
    abort_if(
      $assignment->assignmentSubmissions()->exists(),
      403,
      'Assignment already has some submissions.'
    );

    $this->authorize('delete', $assignment);

    $assignment->delete();
    return $this->ok();
  }

  function store(Institution $institution, AssignmentRequest $request)
  {
    $data = $request->validated();

    (new RecordAssignment($institution, $request->getInstitutionUser(), $data))->create();

    return $this->ok();
  }

  function update(
    AssignmentRequest $request,
    Institution $institution,
    Assignment $assignment
  ) {
    $data = $request->validated();

    (new RecordAssignment($institution, $request->getInstitutionUser(), $data))->update($assignment);
    
    return $this->ok();
  }

}
