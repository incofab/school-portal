<?php

namespace App\Console\Commands;

use App\Enums\ManagerRole;
use App\Models\User;
use Illuminate\Console\Command;

class AssignRoleToUser extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:assign-role {email} {role?}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Assign a role to user's email";

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $email = $this->argument('email');
    $inputRole = $this->hasArgument('role') ? $this->argument('role') : null;
    /** @var User $user */
    $user = User::query()->firstWhere('email', $email);

    if (!$user) {
      $this->comment('Email not found');
      return Command::INVALID;
    }
    if (!$inputRole) {
      $user->syncRoles();
      $this->comment('User role has been removed');
      return Command::SUCCESS;
    }

    if (!in_array($inputRole, ManagerRole::toArray())) {
      $this->comment("$inputRole is invalid");
      return Command::INVALID;
    }

    /** @var Role $role */
    $role = $user->roles()->first();

    if ($role?->name === $inputRole) {
      $this->comment("User already has the role $inputRole");
      return Command::FAILURE;
    }

    $user->syncRoles($inputRole);
    $this->comment("User has been assigned the role ($inputRole)");
    return Command::SUCCESS;
  }
}
