<?php

namespace App\Services\Institutions;

use App\Enums\InstitutionPermission;
use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class InstitutionRoleService
{
  public const GUARD = 'web';

  public function ensureDefaultRoles(Institution $institution): Collection
  {
    foreach (InstitutionUserType::cases() as $role) {
      $institutionRole = Role::query()
        ->withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('guard_name', self::GUARD)
        ->where('name', $role->value)
        ->first();

      if (!$institutionRole) {
        $institutionRole = Role::create([
          'institution_id' => $institution->id,
          'name' => $role->value,
          'description' => $role->description(),
          'default_permissions_seeded' => false,
          'guard_name' => self::GUARD
        ]);
      } else {
        $institutionRole
          ->fill([
            'description' =>
              $institutionRole->description ?: $role->description()
          ])
          ->save();
      }

      if (!$institutionRole->default_permissions_seeded) {
        $this->syncDefaultPermissions($institutionRole);
        $institutionRole->update(['default_permissions_seeded' => true]);
      }
    }

    return $this->forInstitution($institution);
  }

  public function forInstitution(Institution $institution): Collection
  {
    return Role::query()
      ->withoutGlobalScopes()
      ->where('institution_id', $institution->id)
      ->where('guard_name', self::GUARD)
      ->orderBy('name')
      ->get();
  }

  public function forStaff(Institution $institution): Collection
  {
    return $this->forInstitution($institution)
      ->reject(
        fn(Role $role) => in_array(
          $role->name,
          InstitutionUserType::nonStaffRoles(),
          true
        )
      )
      ->values();
  }

  public function findOrCreate(Institution $institution, string $name): Role
  {
    return Role::create([
      'institution_id' => $institution->id,
      'name' => Str::squish($name),
      'guard_name' => self::GUARD
    ]);
  }

  public function create(Institution $institution, array $data): Role
  {
    $role = $this->findOrCreate($institution, $data['name']);
    $role->update([
      'description' => $data['description'] ?? null,
      'default_permissions_seeded' => false
    ]);
    $role->syncPermissions($this->permissions($data['permissions'] ?? []));

    return $role->fresh('permissions');
  }

  public function update(Role $role, array $data): Role
  {
    $role->update([
      'name' => Str::squish($data['name']),
      'description' => $data['description'] ?? null
    ]);
    $role->syncPermissions($this->permissions($data['permissions'] ?? []));

    return $role->fresh('permissions');
  }

  // public function ensurePermissionInventory(): void
  // {
  //   foreach (InstitutionPermission::cases() as $permission) {
  //     Permission::findOrCreate($permission->value, self::GUARD);
  //   }
  // }

  private function permissions(array $permissionNames): Collection
  {
    return Permission::query()
      ->where('guard_name', self::GUARD)
      ->whereIn('name', $permissionNames)
      ->get();
  }

  public function assignDefaultRole(?InstitutionUser $institutionUser): void
  {
    if (!$institutionUser) {
      return;
    }
    $type = $institutionUser->type;
    if (!$type instanceof InstitutionUserType) {
      return;
    }

    $role = Role::query()
      ->withoutGlobalScopes()
      ->where('institution_id', $institutionUser->institution_id)
      ->where('guard_name', self::GUARD)
      ->where('name', $type->value)
      ->first();

    if ($role) {
      $this->assign($institutionUser, $role);
    }
  }

  public function assignMissingDefaultRoles(Institution $institution): int
  {
    $assigned = 0;

    $institution
      ->institutionUsers()
      ->with('roles')
      ->get()
      ->each(function (InstitutionUser $institutionUser) use (&$assigned) {
        if ($institutionUser->roles->isNotEmpty()) {
          return;
        }

        $this->assignDefaultRole($institutionUser);
        $assigned++;
      });

    return $assigned;
  }

  /**
   * Syncs the default permissions for a role based on its name and institution.
   *
   * @param Role $role
   * @return void
   */
  private function syncDefaultPermissions(Role $role): void
  {
    if (!$role->institution_id || $role->guard_name !== self::GUARD) {
      return;
    }

    $roleType = InstitutionUserType::tryFrom($role->name);

    if (!$roleType) {
      return;
    }

    $permissionNames = InstitutionPermission::defaultPermissions($roleType);

    $permissions = in_array('*', $permissionNames, true)
      ? InstitutionPermission::cases()
      : $permissionNames;

    $permissionNames = collect($permissions)
      ->map(
        fn($permission) => $permission instanceof \BackedEnum
          ? $permission->value
          : $permission
      )
      ->all();

    $permissions = Permission::query()
      ->whereIn('name', $permissionNames)
      ->where('guard_name', self::GUARD)
      ->get();

    $role->syncPermissions($permissions);
  }

  public function findForInstitution(
    Institution $institution,
    int $roleId
  ): Role {
    return Role::query()
      ->withoutGlobalScopes()
      ->whereKey($roleId)
      ->where('institution_id', $institution->id)
      ->where('guard_name', self::GUARD)
      ->firstOrFail();
  }

  public function resolveSelection(Institution $institution, int $roleId): Role
  {
    return $this->findForInstitution($institution, $roleId);
  }

  public function resolveStaffSelection(
    Institution $institution,
    int $roleId
  ): Role {
    $role = $this->resolveSelection($institution, $roleId);

    abort_unless(
      $this->forStaff($institution)->contains('id', $role->id),
      422,
      'Please select a staff role.'
    );

    return $role;
  }

  public function assign(InstitutionUser $institutionUser, Role $role): void
  {
    abort_unless(
      (int) $institutionUser->institution_id === (int) $role->institution_id &&
        $role->guard_name === self::GUARD,
      404
    );

    $institutionUser->syncRoles($role);
  }

  public function roleName(InstitutionUser $institutionUser): ?string
  {
    return $institutionUser
      ->roles()
      ->where('roles.institution_id', $institutionUser->institution_id)
      ->value('name');
  }
}
