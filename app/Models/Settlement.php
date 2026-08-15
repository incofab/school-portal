<?php

namespace App\Models;

use App\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Settlement extends BaseModel
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'withdrawal_id' => 'integer',
    'amount' => 'float',
    'processed_at' => 'datetime',
    'status' => SettlementStatus::class
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function withdrawal()
  {
    return $this->belongsTo(Withdrawal::class);
  }

  public function paymentReferences()
  {
    return $this->belongsToMany(PaymentReference::class, 'settlement_payments')
      ->withPivot('amount')
      ->withTimestamps();
  }
}
