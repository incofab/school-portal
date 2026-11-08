<?php

namespace App\Console\Commands\OneTime;

use App\Enums\InstitutionPermission;
use App\Enums\InstitutionUserType;
use App\Models\Role;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class GrantStaffViewResultToExistingRoles extends Command
{
  protected $signature = 'permissions:grant-staff-view-result
    {--dry-run : Report matching roles without changing permissions}
    {--force : Run without the confirmation prompt}';

  protected $description = 'Grant staff-view-result to existing institution teacher and admin roles';

  public function handle(): int
  {
    $permission = Permission::findOrCreate(
      InstitutionPermission::StaffViewResult->value,
      'web'
    );

    $roleNames = [
      InstitutionUserType::Teacher->value,
      InstitutionUserType::Admin->value
    ];
    $roles = Role::query()
      ->withoutGlobalScopes()
      ->whereNotNull('institution_id')
      ->where('guard_name', 'web')
      ->whereIn('name', $roleNames)
      ->with('permissions')
      ->get();

    $rolesToUpdate = $roles
      ->filter(
        fn(Role $role) => !$role->permissions->contains('id', $permission->id)
      )
      ->values();

    $this->info('Existing teacher and admin roles found: ' . $roles->count());
    $this->info('Roles to receive permission: ' . $rolesToUpdate->count());

    if ($this->option('dry-run')) {
      return self::SUCCESS;
    }

    if (
      !$this->option('force') &&
      !$this->confirm('Grant staff-view-result to these roles?')
    ) {
      $this->warn('Cancelled. No role permissions were changed.');
      return self::SUCCESS;
    }

    foreach ($rolesToUpdate as $role) {
      $role->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->info(
      'Granted staff-view-result to ' . $rolesToUpdate->count() . ' roles.'
    );

    return self::SUCCESS;
  }
}
