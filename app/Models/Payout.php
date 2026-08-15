<?php

namespace App\Models;

use App\Enums\PayoutMerchantType;
use App\Enums\PayoutPurposeType;
use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payout extends BaseModel
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'payoutable_id' => 'integer',
    'amount' => 'float',
    'is_processing' => 'boolean',
    'attempt_count' => 'integer',
    'attempted_at' => 'datetime',
    'completed_at' => 'datetime',
    'provider_response' => 'array',
    'merchant' => PayoutMerchantType::class,
    'purpose' => PayoutPurposeType::class,
    'status' => PayoutStatus::class
  ];

  public function payoutable()
  {
    return $this->morphTo('payoutable');
  }
}
