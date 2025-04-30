<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Partner extends Model
{
  use HasFactory, HasRoles;

  protected $guarded = [];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function referralUser()
  {
    return $this->belongsTo(User::class, 'referral_user_id');
  }

  function bankAccounts()
  {
    return $this->morphMany(BankAccount::class, 'accountable');
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
