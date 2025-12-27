<?php

namespace App\Models;

use App\Enums\ManagerRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Enum;
use Spatie\Permission\Traits\HasRoles;

class Partner extends Model
{
  use HasFactory, HasRoles;

  protected $guarded = [];
  protected $casts = [
    'referral_id' => 'integer',
    'commission' => 'float',
    'referral_commission' => 'float',
    'user_id' => 'integer',
    'wallet' => 'float'
  ];

  static function createRule(?User $user = null)
  {
    return [
      ...User::generalRule($user?->id),
      'username' => [
        'required',
        'unique:users,username',
        function ($attr, $value, $fail) {
          if (ctype_digit($value)) {
            $fail('Username cannot contain only digits');
          }
        }
      ],
      'role' => [
        'required',
        new Enum(ManagerRole::class),
        function ($attr, $value, $fail) {
          if ($value === ManagerRole::Admin->value) {
            $fail('Admin role cannot be added through this form');
          }
        }
      ],
      ...self::partnerOnlyRule($user)
    ];
  }

  static function partnerOnlyRule(?User $user = null)
  {
    return [
      'commission' => ['nullable', 'numeric', 'min:0'],
      ...$user?->partner
        ? []
        : ['referral_email' => ['nullable', 'exists:users,email']],
      'referral_commission' => ['nullable', 'numeric', 'min:0']
    ];
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Parent partner. Retrieves the partner that referred the current partner
   */
  public function referral()
  {
    return $this->belongsTo(Partner::class, 'referral_id');
  }

  /**
   * Children partners. All partners referred by the current partner
   */
  public function referrals()
  {
    return $this->hasMany(Partner::class, 'referral_id');
  }

  function bankAccounts()
  {
    return $this->morphMany(BankAccount::class, 'accountable');
  }

  function commissions()
  {
    return $this->hasMany(Commission::class);
  }

  function withdrawals()
  {
    return $this->morphMany(Withdrawal::class, 'withdrawable');
  }

  function userTransactions()
  {
    return $this->morphMany(UserTransaction::class, 'entity');
  }
}
