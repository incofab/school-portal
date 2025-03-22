<?php
namespace App\DTO;

use App\Enums\Payments\PaymentPurpose;
use Illuminate\Database\Eloquent\Model;
use Str;

class PaymentReferenceDto
{
  function __construct(
    public int $institution_id,
    public string $merchant,
    public int|float $amount,
    public PaymentPurpose $purpose,
    private ?Model $payable = null,
    private ?Model $paymentable = null,
    public $user_id = null,
    public ?string $reference = null,
    public ?string $redirect_url = null,
    public array $meta = []
  ) {
    $this->reference = $reference ?? Str::orderedUuid();
  }

  function getPaymentable()
  {
    return $this->paymentable;
  }

  function getPayable()
  {
    return $this->payable;
  }

  function getMeta()
  {
    return $this->meta;
  }

  function toArray(): array
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
      'payable_type' => $this->payable?->getMorphClass()
    ];
  }
}
