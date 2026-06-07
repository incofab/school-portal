<?php

namespace Database\Seeders;

use App\Enums\ManagerRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
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
        $viewAnyActivityLogs = Permission::findOrCreate('activity-logs.view-any');
        $viewInstitutionActivityLogs = Permission::findOrCreate(
            'activity-logs.view-institution'
        );

        foreach (ManagerRole::cases() as $roleType) {
            $role = Role::findOrCreate($roleType->value);

            if ($roleType === ManagerRole::Admin) {
                $role->givePermissionTo($viewAnyActivityLogs);
                $role->givePermissionTo($viewInstitutionActivityLogs);
            }
        }
    }
}
