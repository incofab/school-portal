<?php

namespace App\Http\Controllers\Institutions\Roles;

use App\Enums\RoleGuard;
use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionRoleRequest;
use App\Models\Institution;
use App\Models\Role;
use App\Services\Institutions\InstitutionRoleService;
use App\Support\Audit\SecurityActivityLogger;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
  public function index(Institution $institution)
  {
    $this->authorizeAdmin();

    return Inertia::render('institutions/roles/list-roles', [
      'roles' => Role::query()
        ->where('institution_id', $institution->id)
        ->where('guard_name', RoleGuard::Web->value)
        ->withCount(['institutionUsers', 'permissions'])
        ->orderBy('name')
        ->paginate((int) request()->query('perPage', 100))
    ]);
  }

  public function create(Institution $institution)
  {
    $this->authorizeAdmin();

    return Inertia::render('institutions/roles/create-edit-role', [
      'permissions' => $this->permissions()
    ]);
  }

  public function store(
    InstitutionRoleRequest $request,
    Institution $institution,
    InstitutionRoleService $roleService
  ) {
    $roleService->create($institution, $request->validated());

    return $this->ok(['message' => 'Role created successfully.']);
  }

  public function edit(Institution $institution, Role $role)
  {
    $this->authorizeAdmin();
    $this->assertInstitutionRole($institution, $role);
    $role->load('permissions');

    return Inertia::render('institutions/roles/create-edit-role', [
      'role' => $role,
      'permissions' => $this->permissions()
    ]);
  }

  public function update(
    InstitutionRoleRequest $request,
    Institution $institution,
    Role $role,
    InstitutionRoleService $roleService
  ) {
    $this->authorizeAdmin();
    $this->assertInstitutionRole($institution, $role);
    $oldPermissions = $role
      ->permissions()
      ->pluck('name')
      ->all();
    $updatedRole = $roleService->update($role, $request->validated());

    app(SecurityActivityLogger::class)->permissionChanged(
      currentUser(),
      $updatedRole,
      $oldPermissions,
      $updatedRole->permissions->pluck('name')->all()
    );

    return $this->ok(['message' => 'Role updated successfully.']);
  }

  public function destroy(Institution $institution, Role $role)
  {
    $this->authorizeAdmin();
    $this->assertInstitutionRole($institution, $role);
    $role->delete();

    return $this->ok(['message' => 'Role deleted successfully.']);
  }

  private function authorizeAdmin(): void
  {
    abort_unless(currentUser()?->isInstitutionAdmin(), 403);
  }

  private function assertInstitutionRole(
    Institution $institution,
    Role $role
  ): void {
    abort_unless(
      $role->institution_id === $institution->id &&
        $role->guard_name === RoleGuard::Web->value,
      404
    );
  }

  private function permissions()
  {
    return Permission::query()
      ->where('guard_name', RoleGuard::Web->value)
      ->orderBy('name')
      ->get(['id', 'name']);
  }
}
