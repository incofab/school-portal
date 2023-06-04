<?php
namespace App\Actions;

use App\Http\Requests\CreateStaffRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStaff
{
  // public static function createWithTransaction(array $data)
  // {

  // }

  public static function create(array $data)
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->updateOrCreate(
      ['email' => $data['email']],
      [...collect($data)->except('role'), 'password' => bcrypt('password')]
    );

    $user
      ->institutions()
      ->syncWithPivotValues(
        [currentInstitution()->id],
        ['role' => $data['role']]
      );

    DB::commit();
  }
}
