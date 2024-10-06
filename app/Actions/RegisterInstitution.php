<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterInstitution
{
  public static function run(
    InstitutionGroup $institutionGroup,
    array $data,
    callable $callback = null
  ) {
    DB::beginTransaction();
    $user = $institutionGroup->user;
    $institution = $user
      ->institutions()
      ->withPivotValue('role', InstitutionUserType::Admin)
      ->create([
        ...$data,
        'code' => Institution::generateInstitutionCode(),
        'uuid' => Str::orderedUuid(),
        'user_id' => $user->id,
        'institution_group_id' => $institutionGroup->id
      ]);
    SeedInitialAssessment::run($institution);
    if ($callback) {
      $callback();
    }
    DB::commit();
  }
}
