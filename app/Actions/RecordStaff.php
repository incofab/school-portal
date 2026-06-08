<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStaff
{
  public function __construct(
    private Institution $institution,
    private array $userData
  ) {
  }

  public static function make(Institution $institution, array $userData)
  {
    return new self($institution, $userData);
  }

  public function create(): User
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->create([
      ...collect($this->userData)->except('role'),
      'password' => bcrypt('password')
    ]);

    $this->syncRole($user);

    DB::commit();

    return $user;
  }

  public function update(User $user): User
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

    return $user;
  }

  public function syncRole(User $user)
  {
    $user
      ->institutions()
      ->syncWithPivotValues(
        [$this->institution->id],
        ['role' => $this->userData['role']]
      );
  }
}
