<?php

namespace Database\Factories;

use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
  protected $model = Partner::class;

  public function definition()
  {
    return [
      'user_id' => User::factory()->partnerManager(),
      'commission' => 30,
      'referral_id' => null,
      'referral_commission' => 0,
      'wallet' => 0,
      'created_at' => now(),
      'updated_at' => now()
    ];
  }

  public function institutionGroup(InstitutionGroup $institutionGroup)
  {
    return $this->afterCreating(fn(Partner $partner) =>
      $institutionGroup
      ->fill(['partner_user_id' => $partner->user_id])
      ->save()
    );
  }

  public function withReferral(?InstitutionGroup $institutionGroup = null)
  {
    return $this->afterCreating(function(Partner $partner) use ($institutionGroup) {
      $newpartner = Partner::factory()->institutionGroup($institutionGroup)->create([
        'commission' => 20,
        'referral_commission' => 10,
        'referral_id' => $partner->id,
      ]);
    });
  }
}
