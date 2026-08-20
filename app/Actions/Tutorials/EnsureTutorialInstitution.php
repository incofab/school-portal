<?php

namespace App\Actions\Tutorials;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EnsureTutorialInstitution
{
  /**
   * Idempotently creates (or fetches) the deterministic admin user and
   * institution shared by every tutorial-video seed command. Safe to call
   * repeatedly — never duplicates the admin or their institution.
   *
   * @return array{0: User, 1: Institution}
   */
  public static function run(): array
  {
    $email = config('tutorial.demo_email');
    $password = config('tutorial.demo_password');

    $user = User::withTrashed()->updateOrCreate(
      ['email' => $email],
      [
        'first_name' => 'Tutorial',
        'last_name' => 'Demo',
        'other_names' => '',
        'phone' => '08099999999',
        'email_verified_at' => now(),
        'password' => Hash::make($password),
        'deleted_at' => null
      ]
    );

    $institutionUser = $user
      ->institutionUsers()
      ->with('institution')
      ->first();

    if ($institutionUser) {
      $institution = $institutionUser->institution;
    } else {
      // Institution::factory() creates the admin InstitutionUser link automatically
      // via InstitutionFactory::configure() using the given user_id.
      $institution = Institution::factory()->create([
        'user_id' => $user->id,
        'name' => 'Tutorial Demo Academy'
      ]);
    }

    return [$user, $institution];
  }
}
