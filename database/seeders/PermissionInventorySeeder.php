<?php

namespace Database\Seeders;

use App\Enums\InstitutionPermission;
use App\Enums\RoleGuard;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionInventorySeeder extends Seeder
{
  public function run(): void
  {
    foreach (InstitutionPermission::cases() as $permission) {
      Permission::findOrCreate($permission->value, RoleGuard::Web->value);
    }
  }
}
