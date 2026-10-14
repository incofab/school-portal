<?php

namespace App\Http\Controllers\Institutions\Designations;

use App\Enums\DesignationScope;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Designation;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DesignationController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(Institution $institution, Request $request)
  {
    $session = AcademicSession::query()
      ->whereKey(
        $request->integer('academic_session_id') ?:
        AcademicSession::query()
          ->where('is_active', true)
          ->value('id')
      )
      ->first();
    $query = Designation::query()
      ->with('eligibleClasses')
      ->withCount([
        'activeAssignments as current_assignment_count' => fn($q) => $q->when(
          $session,
          fn($sq) => $sq->where('academic_session_id', $session->id)
        )
      ])
      ->orderBy('title');

    return Inertia::render('institutions/designations/list-designations', [
      'designations' => paginateFromRequest($query),
      'academicSession' => $session,
      'canManageStaffDesignations' =>
        currentInstitutionUser()?->isAdmin() ?? false
    ]);
  }

  public function create(Institution $institution)
  {
    return Inertia::render(
      'institutions/designations/create-edit-designation',
      [
        'classifications' => Classification::query()
          ->orderBy('title')
          ->get(['id', 'title']),
        'canManageStaffDesignations' =>
          currentInstitutionUser()?->isAdmin() ?? false
      ]
    );
  }

  public function store(
    Institution $institution,
    Request $request,
    \App\Actions\Designations\SaveDesignation $action
  ) {
    $data = $request->validate($this->rules());
    abort_if(
      !currentInstitutionUser()?->isAdmin() &&
        !filter_var($data['is_for_students'], FILTER_VALIDATE_BOOLEAN),
      403,
      'Only administrators can create staff designations.'
    );
    $action->execute($institution, $data);
    return $this->ok(['message' => 'Designation created successfully.']);
  }

  public function edit(Institution $institution, Designation $designation)
  {
    $this->assertOwned($institution, $designation);
    $this->authorizeDesignationType($designation);
    return Inertia::render(
      'institutions/designations/create-edit-designation',
      [
        'designation' => $designation->load('eligibleClasses'),
        'classifications' => Classification::query()
          ->orderBy('title')
          ->get(['id', 'title']),
        'canManageStaffDesignations' =>
          currentInstitutionUser()?->isAdmin() ?? false
      ]
    );
  }

  public function update(
    Institution $institution,
    Designation $designation,
    Request $request,
    \App\Actions\Designations\SaveDesignation $action
  ) {
    $this->assertOwned($institution, $designation);
    $this->authorizeDesignationType($designation);
    $data = $request->validate($this->rules($designation));
    abort_if(
      !currentInstitutionUser()?->isAdmin() &&
        !filter_var($data['is_for_students'], FILTER_VALIDATE_BOOLEAN),
      403,
      'Only administrators can create or update staff designations.'
    );
    $action->execute($institution, $data, $designation);
    return $this->ok(['message' => 'Designation updated successfully.']);
  }

  public function show(Institution $institution, Designation $designation)
  {
    $this->assertOwned($institution, $designation);
    $designation->load('eligibleClasses');
    $assignments = $designation
      ->assignments()
      ->with([
        'institutionUser.user',
        'institutionUser.student',
        'classification',
        'academicSession',
        'assignedBy'
      ])
      ->latest('assigned_at')
      ->paginate(20);

    return Inertia::render('institutions/designations/show-designation', [
      'designation' => $designation,
      'assignments' => $assignments,
      'academicSessions' => AcademicSession::query()
        ->orderByDesc('id')
        ->get(['id', 'title', 'is_active']),
      'canManageStaffDesignations' =>
        currentInstitutionUser()?->isAdmin() ?? false
    ]);
  }

  public function deactivate(Institution $institution, Designation $designation)
  {
    $this->assertOwned($institution, $designation);
    $this->authorizeDesignationType($designation);
    $designation->update(['is_active' => false]);
    return $this->ok([
      'message' =>
        'Designation deactivated. Assignment history has been preserved.'
    ]);
  }

  private function assertOwned(
    Institution $institution,
    Designation $designation
  ): void {
    abort_unless(
      (int) $designation->institution_id === (int) $institution->id,
      404
    );
  }

  private function authorizeDesignationType(Designation $designation): void
  {
    abort_if(
      !$designation->is_for_students && !currentInstitutionUser()?->isAdmin(),
      403,
      'Only administrators can manage staff designations.'
    );
  }

  private function rules(?Designation $designation = null): array
  {
    return [
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('designations', 'title')
          ->where('institution_id', currentInstitution()->id)
          ->ignore($designation?->id)
      ],
      'description' => ['nullable', 'string'],
      'is_for_students' => ['required', 'boolean'],
      'scope' => ['required', Rule::enum(DesignationScope::class)],
      'max_holders' => ['required', 'integer', 'min:1'],
      'is_active' => ['required', 'boolean'],
      'eligible_class_ids' => ['nullable', 'array'],
      'eligible_class_ids.*' => ['integer', 'distinct']
    ];
  }
}
