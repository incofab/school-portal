<?php

namespace App\Actions\Tutorials;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EnsureTutorialInstitution
{
  /**
   * Idempotently creates (or fetches) a deterministic admin user and
   * institution for a tutorial-video seed command. Safe to call repeatedly
   * — never duplicates the admin or their institution.
   *
   * Defaults to the single admin/institution shared by most tutorial seed
   * commands (`config('tutorial.demo_email')` / "Tutorial Demo Academy").
   * Pass `$email`/`$institutionName` to get a separate, isolated
   * admin/institution instead — e.g. the school-onboarding-walkthrough
   * tutorial needs a genuinely *empty* school (no classes/subjects/
   * students/teachers), which the shared demo institution can't guarantee
   * once other tutorials have seeded data into it.
   *
   * @return array{0: User, 1: Institution}
   */
  public static function run(
    ?string $email = null,
    ?string $institutionName = null
  ): array {
    $email ??= config('tutorial.demo_email');
    $password = config('tutorial.demo_password');
    $institutionName ??= 'Tutorial Demo Academy';

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
        'name' => $institutionName
      ]);
    }

    return [$user, $institution];
  }
}
