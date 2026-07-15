<?php

use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\User;

use function Pest\Laravel\actingAs;

it(
  'allows a manager admin to change an institution group partner user by email',
  function () {
    $admin = User::factory()
      ->adminManager()
      ->create();
    $currentPartner = Partner::factory()->create();
    $newPartner = Partner::factory()->create();
    $institutionGroup = InstitutionGroup::factory()->create([
      'partner_user_id' => $currentPartner->user_id
    ]);

    actingAs($admin)
      ->postJson(
        route('managers.institution-groups.update-partner-user', [
          $institutionGroup
        ]),
        ['email' => $newPartner->user->email]
      )
      ->assertOk();

    expect($institutionGroup->fresh()->partner_user_id)->toBe(
      $newPartner->user_id
    );
  }
);

it('rejects reassignment to an email that is not a partner user', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();
  $institutionGroup = InstitutionGroup::factory()->create();
  $user = User::factory()->create();

  actingAs($admin)
    ->postJson(
      route('managers.institution-groups.update-partner-user', [
        $institutionGroup
      ]),
      ['email' => $user->email]
    )
    ->assertUnprocessable()
    ->assertJsonValidationErrors('email');
});

it(
  'prevents partner users from changing an institution group partner user',
  function () {
    $partner = Partner::factory()->create();
    $newPartner = Partner::factory()->create();
    $institutionGroup = InstitutionGroup::factory()->create([
      'partner_user_id' => $partner->user_id
    ]);

    actingAs($partner->user)
      ->postJson(
        route('managers.institution-groups.update-partner-user', [
          $institutionGroup
        ]),
        ['email' => $newPartner->user->email]
      )
      ->assertForbidden();
  }
);
