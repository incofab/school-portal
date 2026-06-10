<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
  private array $permissions = [
    'activity-logs.view-any',
    'activity-logs.view-institution'
  ];

  public function up(): void
  {
    $permissionIds = DB::table('permissions')
      ->whereIn('name', $this->permissions)
      ->pluck('id');

    if ($permissionIds->isEmpty()) {
      return;
    }

    DB::table('role_has_permissions')
      ->whereIn('permission_id', $permissionIds)
      ->delete();
    DB::table('model_has_permissions')
      ->whereIn('permission_id', $permissionIds)
      ->delete();
    DB::table('permissions')
      ->whereIn('id', $permissionIds)
      ->delete();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
  }

  public function down(): void
  {
    foreach ($this->permissions as $permission) {
      DB::table('permissions')->updateOrInsert(
        ['name' => $permission, 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
      );
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
  }
};
