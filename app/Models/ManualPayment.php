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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ManualPayment extends Model implements PaymentRecord
{
    use HasFactory, InstitutionScope, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'institution_id' => 'integer',
        'user_id' => 'integer',
        'bank_account_id' => 'integer',
        'payable_id' => 'integer',
        'paymentable_id' => 'integer',
        'confirmed_by_user_id' => 'integer',
        'rejected_by_user_id' => 'integer',
        'amount' => 'float',
        'paid_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'processed_at' => 'datetime',
        'method' => PaymentMethod::class,
        'purpose' => PaymentPurpose::class,
        'status' => PaymentStatus::class,
        'meta' => AsArrayObject::class,
        'payload' => AsArrayObject::class,
    ];

    public static function generateReference(): string
    {
        return (string) Str::orderedUuid();
    }

    public function confirmPayment(?User $user = null): void
    {
        $this->fill([
            'status' => PaymentStatus::Confirmed,
            'confirmed_by_user_id' => $user?->id ?? currentUser()?->id,
            'reviewed_at' => now(),
            'processed_at' => now(),
        ])->save();
    }

    public function cancelPayment(
        ?User $user = null,
        ?string $reviewNote = null
    ): void {
        $this->fill([
            'status' => PaymentStatus::Cancelled,
            'rejected_by_user_id' => $user?->id,
            'review_note' => $reviewNote,
            'reviewed_at' => now(),
        ])->save();
    }

    public function getPaymentMerchant(): PaymentMerchantType
    {
        return PaymentMerchantType::Manual;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->method ?? PaymentMethod::Bank;
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

    public function scopePendingFirst($query)
    {
        return $query->orderByRaw('status = ? desc', [
            PaymentStatus::Pending->value,
        ]);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function paymentable()
    {
        return $this->morphTo();
    }
}
