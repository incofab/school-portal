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
    $this->authorize('view', $assignment);
    $institutionUser = currentInstitutionUser();
    
    abort_if(
      $institutionUser->isStudent() && now() > $assignment->expires_at,
      403,
      'Submission Deadline has passed.'
    );

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
