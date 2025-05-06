<?php
namespace App\Actions\RecordUsers;

use App\Enums\InstitutionUserType;
use App\Enums\ManagerRole;
use App\Models\Partner;
use App\Models\PartnerRegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordPartner
{
  function __construct()
  {
  }

  public static function make()
  {
    return new self();
  }

  /**
   * @var array {
   *  first_name: string,
   *  last_name: string,
   *  other_names?: string,
   *  phone: string,
   *  gender: string,
   *  email: string,
   *  username: string,
   *  role: string,
   *  commission: float,
   *  referral_email: string,
   *  referral_commission: float,
   * } $userData
   */
  public function create(array $userData)
  {
    DB::beginTransaction();

    $user = User::query()->create([
      ...collect($userData)
        ->except('role', 'commission', 'referral_email', 'referral_commission')
        ->toArray(),
      'password' => bcrypt('password')
    ]);
    $user->assignRole($userData['role']);

    //= Create Partner's Record
    if ($userData['role'] === ManagerRole::Partner->value) {
      $refUser = User::where('email', $userData['referral_email'])->first();

      Partner::create([
        'user_id' => $user->id,
        'commission' => $userData['commission'],
        'referral_id' => $refUser?->partner?->id,
        'referral_commission' => $userData['referral_commission'] ?? null
      ]);
    }

    DB::commit();
  }


  /**
   * @param array{
   *  commission: float,
   *  referral_email?: string,
   *  referral_commission?: float,
   * } $extraData
   */
  function createFromPartnerRequest(PartnerRegistrationRequest $partnerRegistrationRequest, array $extraData)
  {
    $this->create([
      ...$partnerRegistrationRequest->only('first_name', 'last_name', 'other_names', 'phone', 'gender', 'email', 'username'),
      ...$extraData,
      'role' => ManagerRole::Partner->value
    ]);

    $partnerRegistrationRequest->delete();
  }

}
