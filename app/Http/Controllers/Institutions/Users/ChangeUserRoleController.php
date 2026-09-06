<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Enums\RoleGuard;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Services\Institutions\InstitutionRoleService;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\SecurityActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChangeUserRoleController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    InstitutionUser $suppliedInstitutionUser,
    InstitutionRoleService $roleService
  ) {
    $data = $request->validate([
      'role' => [
        'required',
        'integer',
        Rule::exists('roles', 'id')->where(
          fn($query) => $query
            ->where('institution_id', $institution->id)
            ->where('guard_name', RoleGuard::Web->value)
        )
      ]
    ]);

    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    abort_unless(
      (int) $suppliedInstitutionUser->institution_id === (int) $institution->id,
      404
    );

    $role = $roleService->findForInstitution($institution, (int) $data['role']);
    $suppliedInstitutionUser->load('roles');
    $prevRole =
      $suppliedInstitutionUser->roles->pluck('name')->implode(', ') ?:
      $suppliedInstitutionUser->type?->value ?:
      '';

    ModelAudit::withoutAuditingFor(InstitutionUser::class, function () use (
      $suppliedInstitutionUser,
      $roleService,
      $role
    ) {
      $roleService->assign($suppliedInstitutionUser, $role);
    });
    $suppliedInstitutionUser->loadMissing('user');

    app(SecurityActivityLogger::class)->roleChanged(
      currentUser(),
      $suppliedInstitutionUser,
      $institution,
      $prevRole,
      $role->name
    );

    return $this->ok();
  }
}
