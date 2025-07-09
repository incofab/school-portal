<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdrawal extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'bank_account_id' => 'integer',
    'processed_by_user_id' => 'integer',
    'paid_at' => 'datetime',
    'withdrawable_id' => 'integer',
    'amount' => 'float',
    'paymentable_id' => 'integer',
    'status' => WithdrawalStatus::class
  ];

  function markAsProcessed(?User $user, $status, $remark)
  {
    $this->fill([
      'processed_by_user_id' => $user?->id,
      'status' => $status,
      'remark' => $remark,
      'paid_at' => now()
    ])->save();
  }

  function scopeIsProcessed($query, $forProcessed = true)
  {
    return $forProcessed
      ? $query->whereNotNull('paid_at')
      : $query->whereNull('paid_at');
  }

  // Partner | InstitutionGroup
  public function withdrawable()
  {
    return $this->morphTo('withdrawable');
  }

  public function bankAccount()
  {
    return $this->belongsTo(BankAccount::class);
  }

  public function processedBy()
  {
    return $this->belongsTo(User::class, 'processed_by_user_id');
  }

  function userTransaction()
  {
    return $this->morphOne(UserTransaction::class, 'transactionable');
  }
}
