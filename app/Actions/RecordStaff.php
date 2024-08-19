<?php
namespace App\Actions;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStaff
{
  function __construct(
    private Institution $institution,
    private array $userData
  ) {
  }

  public static function make(Institution $institution, array $userData)
  {
    return new self($institution, $userData);
  }

  public function create()
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->create([
      ...collect($this->userData)->except('role'),
      'password' => bcrypt('password')
    ]);

    $this->syncRole($user);

    DB::commit();
  }

  function update(User $user)
  {
    DB::beginTransaction();

    $user
      ->fill(
        collect($this->userData)
          ->except('role')
          ->toArray()
      )
      ->save();
    DB::commit();
  }

  function syncRole(User $user)
  {
    $user
      ->institutions()
      ->syncWithPivotValues(
        [$this->institution->id],
        ['role' => $this->userData['role']]
      );
  }
}
