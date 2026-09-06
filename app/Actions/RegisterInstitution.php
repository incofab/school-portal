<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Services\Institutions\InstitutionRoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterInstitution
{
  public static function run(
    InstitutionGroup $institutionGroup,
    array $data,
    ?callable $callback = null
  ) {
    DB::beginTransaction();
    $user = $institutionGroup->user;
    $institution = $user
      ->institutions()
      ->withPivotValue('type', InstitutionUserType::Admin)
      ->create([
        ...$data,
        'code' => Institution::generateInstitutionCode(),
        'uuid' => Str::orderedUuid()->toString(),
        'user_id' => $user->id,
        'institution_group_id' => $institutionGroup->id
      ]);

    SeedSetupData::run($institution);

    // $roleService = app(InstitutionRoleService::class);
    // $roleService->ensurePermissionInventory();
    // $roleService->ensureDefaultRoles($institution);
    // $roleService->assignDefaultRole(
    //   $institution
    //     ->institutionUsers()
    //     ->where('user_id', $user->id)
    //     ->firstOrFail()
    // );

    if ($callback) {
      $callback();
    }

    DB::commit();
  }
}
