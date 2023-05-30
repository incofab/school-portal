<?php
namespace App\Actions;

use App\Http\Requests\CreateStaffRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStaff
{
  public static function create(CreateStaffRequest $request)
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->updateOrCreate(
      ['email' => $request->email],
      [
        ...collect($request->validated())->except('role'),
        'password' => bcrypt('password')
      ]
    );

    $user
      ->institutions()
      ->syncWithPivotValues(
        [currentInstitution()->id],
        ['role' => $request->role]
      );

    DB::commit();
  }
}
