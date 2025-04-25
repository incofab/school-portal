<?php

namespace App\Models;

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentReference extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'user_id' => 'integer',
    'payable_id' => 'integer',
    'paymentable_id' => 'integer',
    'merchant' => PaymentMerchantType::class,
    'status' => PaymentStatus::class,
    'method' => PaymentMethod::class,
    'purpose' => PaymentPurpose::class,
    'meta' => AsArrayObject::class,
    'payload' => AsArrayObject::class
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

  /**
   * The entity making the payment. Cannot be null
   * Morphs to User | InstitutionGroup
   */
  public function payable()
  {
    return $this->morphTo();
  }

  /**
   * The entity this payment is meant for, it is nullable
   * Morphs to AdmissionForm|null
   */
  function paymentable()
  {
    return $this->morphTo();
  }
}
