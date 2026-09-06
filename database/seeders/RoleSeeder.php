<?php

namespace Database\Seeders;

use App\Enums\ManagerRole;
use App\Enums\RoleGuard;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Role::query()
      ->whereNull('institution_id')
      ->where('name', 'admin')
      ->where(
        fn($q) => $q
          ->where('guard_name', RoleGuard::Web->value)
          ->orWhereNull('guard_name')
      )
      ->update(['name' => ManagerRole::ManagerAdmin->value]);

    foreach (ManagerRole::cases() as $roleType) {
      Role::query()->firstOrCreate(
        [
          'institution_id' => null,
          'name' => $roleType->value
        ],
        ['guard_name' => RoleGuard::Web->value]
      );
    }
  }
}
