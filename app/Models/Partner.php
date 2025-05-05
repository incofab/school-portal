<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Parent partner. Retrieves the partner the referred the current partner
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
