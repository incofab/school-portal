<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\User;
use App\Enums\InstitutionUserType;
use App\Services\Institutions\InstitutionRoleService;
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
    $this->syncRole($user, true);
    DB::commit();

    return $user;
  }

  public function syncRole(User $user, bool $isUpdating = false): void
  {
    $roleService = app(InstitutionRoleService::class);
    $institutionUser = $user
      ->institutionUsers()
      ->firstOrCreate(
        ['institution_id' => $this->institution->id],
        ['type' => InstitutionUserType::Teacher]
      );

    if (empty($this->userData['role'])) {
      if (!$isUpdating) {
        $roleService->assignDefaultRole($institutionUser);
      }
      return;
    }

    $roleService->assign(
      $institutionUser,
      $roleService->resolveStaffSelection(
        $this->institution,
        (int) $this->userData['role']
      )
    );
  }
}
