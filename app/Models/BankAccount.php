<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends BaseModel
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  protected $casts = [
    'is_primary' => 'boolean',
    // 'institution_id' => 'integer',
    'accountable_id' => 'integer'
  ];

  // Partner | InstitutionGroup
  public function accountable()
  {
    return $this->morphTo('accountable');
  }

  public function withdrawals()
  {
    return $this->hasMany(Withdrawal::class);
  }

  public function validWithdrawals()
  {
    return $this->withdrawals()->whereIn('status', [
      WithdrawalStatus::Pending,
      WithdrawalStatus::Paid
    ]);
  }
}
