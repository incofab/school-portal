<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterInstitutionGroup
{
  public static function run(
    User $partnerUser,
    array $userData,
    array $institutionGroupData,
    callable $callback = null
  ) {
    DB::beginTransaction();
    $user = User::query()->create($userData);
    $partnerUser
      ->partnerInstitutionGroups()
      ->create([...$institutionGroupData, 'user_id' => $user->id]);
    if ($callback) {
      $callback();
    }
    DB::commit();
  }
}
