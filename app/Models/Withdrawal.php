<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdrawal extends Model
{
  use HasFactory;

  protected $guarded = [];

  // Partner | InstitutionGroup
  public function withdrawable()
  {
    return $this->morphTo('withdrawable');
  }

  public function bankAccount()
  {
    return $this->belongsTo(BankAccount::class);
  }

  function userTransaction()
  {
    return $this->morphOne(UserTransaction::class, 'transactionable');
  }
}
