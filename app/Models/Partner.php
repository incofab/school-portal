<?php

namespace App\Models;

use App\Casts\TrimDecimal;
use App\Enums\ManagerRole;
use App\Enums\PartnerUserRole;
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
    'wallet' => TrimDecimal::class
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
      'name' => ['nullable', 'string', 'max:255'],
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

  public function partnerUsers()
  {
    return $this->hasMany(PartnerUser::class);
  }

  public function users()
  {
    return $this->belongsToMany(User::class, 'partner_users')
      ->withPivot('role')
      ->withTimestamps();
  }

  public function adminUsers()
  {
    return $this->users()->wherePivot('role', PartnerUserRole::Admin->value);
  }

  public function staffUsers()
  {
    return $this->users()->wherePivot('role', PartnerUserRole::Staff->value);
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
