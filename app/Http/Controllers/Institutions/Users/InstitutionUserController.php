<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Actions\RecordStaff;
use App\Actions\Users\DownloadStaffRecordingSheet;
use App\Actions\Users\InsertStaffFromRecordingSheet;
use App\Enums\InstitutionUserType;
use App\Enums\RoleGuard;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStaffRequest;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Services\Institutions\InstitutionRoleService;
use App\Support\Audit\ModelAudit;
use App\Rules\ExcelRule;
use App\Support\Audit\SecurityActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Storage;

class InstitutionUserController extends Controller
{
  public function create(
    Institution $institution,
    InstitutionRoleService $roleService
  ) {
    abort_unless(currentUser()->isInstitutionAdmin(), 403);

    return inertia('institutions/users/create-edit-user', [
      'roles' => $roleService->forStaff($institution)
    ]);
  }

  public function store(Institution $institution, CreateStaffRequest $request)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    $data = $request->validated();
    $user = ModelAudit::withoutAuditingFor(
      [User::class, InstitutionUser::class],
      fn() => RecordStaff::make($institution, $data)->create()
    );

    $assignedRole =
      $user
        ->institutionUsers()
        ->where('institution_id', $institution->id)
        ->first()
        ?->roles()
        ->value('name') ?? '';

    app(SecurityActivityLogger::class)->userCreated(
      currentUser(),
      $user,
      $institution,
      $assignedRole
    );

    return $this->ok();
  }

  public function downloadTemplate(Institution $institution)
  {
    $excelWriter = DownloadStaffRecordingSheet::run();
    $filename = 'staff-recording-template.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename);
  }

  public function uploadStaff(Request $request, Institution $institution)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    $request->validate([
      'file' => ['required', 'file', new ExcelRule($request->file('file'))],
      'role' => [
        'required',
        'integer',
        Rule::exists('roles', 'id')->where(
          fn($query) => $query
            ->where('institution_id', $institution->id)
            ->where('guard_name', RoleGuard::Web->value)
            ->whereNotIn('name', InstitutionUserType::nonStaffRoles())
        )
      ]
    ]);
    InsertStaffFromRecordingSheet::run(
      $institution,
      $request->file,
      $request->role
    );

    return $this->ok();
  }

  public function idCards(
    Institution $institution,
    ?Classification $classification = null
  ) {
    if (!empty($classification)) {
      // Returns Students
      $persons = $classification
        ->students()
        ->with('user')
        ->get();
    } else {
      // Returns Staff
      $persons = InstitutionUser::whereNotIn('type', [
        InstitutionUserType::Student->value,
        InstitutionUserType::Alumni->value
      ])
        ->with('user')
        ->get();
    }

    return inertia('institutions/students/staff-id-cards', [
      'persons' => $persons
    ]);
  }
}
