<?php
namespace Database\Seeders;

use App\Enums\ManagerRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    foreach (ManagerRole::cases() as $key => $roleType) {
      Role::findOrCreate($roleType->value);
    }
  }
}
