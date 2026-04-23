<?php

namespace App\Contracts\Payments;

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface PaymentRecord
{
    public function confirmPayment(?User $user = null): void;

    public function cancelPayment(
        ?User $user = null,
        ?string $reviewNote = null
    ): void;

    public function getPaymentMerchant(): PaymentMerchantType;

    public function getPaymentMethod(): PaymentMethod;

    public function getInstitution(): Institution;

    public function getReference(): string;

    public function getAmount(): float;

    public function getStatus(): PaymentStatus;

    public function getPurpose(): PaymentPurpose;

    public function getUser(): ?User;

    public function getPayable(): ?Model;

    public function getPaymentable(): ?Model;

    public function getPaymentMeta(): array;

    public function getModel(): Model;
}
