<?php

namespace App\Models;

use App\Enums\Payments\PaymentMerchant;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'institution_group_id' => 'integer',
    'type' => TransactionType::class,
    'meta' => 'array'
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  // Morph to Funding
  public function transactionable()
  {
    return $this->morphTo();
  }
}
