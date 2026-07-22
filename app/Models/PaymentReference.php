<?php

namespace App\Models;

use App\Contracts\Payments\PaymentRecord;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// User: The user making the payment or inst admin user
// Payable: The entity making the payment
// Paymentable: The entity this payment is meant for, it is nullable
class PaymentReference extends BaseModel implements PaymentRecord
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'user_id' => 'integer',
    'payable_id' => 'integer',
    'paymentable_id' => 'integer',
    'processed_at' => 'datetime',
    'merchant' => PaymentMerchantType::class,
    'status' => PaymentStatus::class,
    'method' => PaymentMethod::class,
    'purpose' => PaymentPurpose::class,
    'meta' => AsArrayObject::class,
    'payload' => AsArrayObject::class
  ];

  public function confirmPayment(?User $user = null): void
  {
    $this->fill([
      'status' => PaymentStatus::Confirmed,
      'processed_at' => now()
    ])->save();
  }

  public function cancelPayment(
    ?User $user = null,
    ?string $reviewNote = null
  ): void {
    $this->fill([
      'status' => PaymentStatus::Cancelled,
      'processed_at' => now()
    ])->save();
  }

  public function getPaymentMerchant(): PaymentMerchantType
  {
    return $this->merchant;
  }

  public function getPaymentMethod(): PaymentMethod
  {
    if ($this->merchant === PaymentMerchantType::UserWallet) {
      return PaymentMethod::Wallet;
    }

    return $this->method ?? PaymentMethod::Card;
  }

  public function getInstitution(): Institution
  {
    return $this->institution;
  }

  public function getReference(): string
  {
    return $this->reference;
  }

  public function getAmount(): float
  {
    return $this->amount;
  }

  public function getStatus(): PaymentStatus
  {
    return $this->status;
  }

  public function getPurpose(): PaymentPurpose
  {
    return $this->purpose;
  }

  public function getUser(): ?User
  {
    return $this->user;
  }

  public function getPayable(): ?Model
  {
    return $this->payable;
  }

  public function getPaymentable(): ?Model
  {
    return $this->paymentable;
  }

  public function getPaymentMeta(): array
  {
    return $this->meta ? $this->meta->getArrayCopy() : [];
  }

  public function getModel(): Model
  {
    return $this;
  }

  public function scopeConfirmed($query)
  {
    return $query->where('payment_references.status', PaymentStatus::Confirmed);
  }

  public function scopeIsProcessed($query, $forProcessed = true)
  {
    return $forProcessed
      ? $query->whereNotNull('processed_at')
      : $query->whereNull('processed_at');
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public static function generateReference($username = null): string
  {
    return Str::orderedUuid()->toString();
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
  public function paymentable()
  {
    return $this->morphTo();
  }
}
