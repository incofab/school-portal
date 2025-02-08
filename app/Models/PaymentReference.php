<?php

namespace App\Models;

use App\Enums\Payments\PaymentMerchant;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentReference extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'user_id' => 'integer',
    'payable_id' => 'integer',
    'merchant' => PaymentMerchant::class,
    'status' => PaymentStatus::class,
    'method' => PaymentMethod::class,
    'purpose' => PaymentPurpose::class,
    'meta' => 'array',
    'payload' => 'array'
  ];

  function confirmPayment()
  {
    $this->fill(['status' => PaymentStatus::Confirmed])->save();
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  static function generateReference($username = null)
  {
    return Str::orderedUuid();
  }

  // Morph to User | InstitutionGroup
  public function payable()
  {
    return $this->morphTo();
  }
}