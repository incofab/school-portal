<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Services\Institutions\InstitutionRoleService;
use Illuminate\Console\Command;

class SeedInstitutionRoles extends Command
{
  protected $signature = 'institutions:seed-roles';

  protected $description = 'Provision built-in roles and permissions for every institution';

  public function handle(InstitutionRoleService $roleService): int
  {
    // $roleService->ensurePermissionInventory();

    $institutions = Institution::query()
      ->orderBy('id')
      ->get();
    foreach ($institutions as $institution) {
      $roleService->ensureDefaultRoles($institution);
      $assigned = $roleService->assignMissingDefaultRoles($institution);

      $this->line(
        "Provisioned roles for {$institution->name} (assigned {$assigned} users)"
      );
    }

    $this->info(
      "Completed. Provisioned {$institutions->count()} institutions."
    );

    return self::SUCCESS;
  }
}
