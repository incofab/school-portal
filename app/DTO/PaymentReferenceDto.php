<?php

namespace App\DTO;

use App\Enums\Payments\PaymentPurpose;
use Illuminate\Database\Eloquent\Model;
use Str;

class PaymentReferenceDto
{
    public function __construct(
        public int $institution_id,
        public string $merchant,
        public int|float $amount,
        public PaymentPurpose $purpose,
        private ?Model $payable = null, // The entity making the payment
        private ?Model $paymentable = null, // The entity this payment is made for
        public $user_id = null, // The logged in user making initiating this transaction
        public ?string $reference = null,
        public ?string $redirect_url = null,
        public array $meta = [],
        public array $manualData = []
    ) {
        $this->reference = $reference ?? Str::orderedUuid();
    }

    public function getPaymentable()
    {
        return $this->paymentable;
    }

    public function getPayable()
    {
        return $this->payable;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function toArray(): array
    {
        return [
            'institution_id' => $this->institution_id,
            'merchant' => $this->merchant,
            'amount' => $this->amount,
            'purpose' => $this->purpose,
            'user_id' => $this->user_id,
            'reference' => $this->reference,
            'redirect_url' => $this->redirect_url,
            'meta' => $this->meta,
            'paymentable_id' => $this->paymentable?->id,
            'paymentable_type' => $this->paymentable?->getMorphClass(),
            'payable_id' => $this->payable?->id,
            'payable_type' => $this->payable?->getMorphClass(),
        ];
    }
}
