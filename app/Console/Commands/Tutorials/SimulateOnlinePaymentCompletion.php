<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Payments\FeePaymentHandler;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentStatus;
use App\Models\Fee;
use App\Models\PaymentReference;
use Illuminate\Console\Command;

/**
 * Tutorial-only helper: marks an in-progress ONLINE fee payment as
 * completed without a real bank transfer.
 *
 * Why this exists: the tutorial video generator drives the real Monnify
 * sandbox checkout so the recording shows the genuine hand-off UI, but
 * Monnify's sandbox only reports a transaction as successful once an
 * actual (test) bank transfer has landed against the one-time account it
 * generates — clicking "I've transferred the money" does not, by itself,
 * complete it. There is no way to fabricate that transfer from this app.
 *
 * This command simulates the *result* of that transfer using the exact
 * same business logic the real callback uses (`FeePaymentHandler::create`,
 * `PaymentReference::confirmPayment`) — it just skips the Monnify API
 * verification step, which cannot succeed without real money movement.
 * Refuses to run outside local/testing environments.
 */
class SimulateOnlinePaymentCompletion extends Command
{
  protected $signature = 'tutorial:simulate-monnify-payment {reference}';

  protected $description = 'Tutorial-only: mark a pending Monnify PaymentReference as completed without a real bank transfer, for demo-video purposes';

  public function handle(): int
  {
    if (!app()->environment(['local', 'testing'])) {
      $this->error(
        'Refusing to run outside local/testing environments — this command fakes payment completion and must never touch real data.'
      );

      return self::FAILURE;
    }

    $reference = $this->argument('reference');

    $paymentReference = PaymentReference::query()
      ->where('reference', $reference)
      ->where('merchant', PaymentMerchantType::Monnify)
      ->with('institution', 'user', 'payable', 'paymentable')
      ->first();

    if (!$paymentReference) {
      $this->error("No pending Monnify payment reference found: {$reference}");

      return self::FAILURE;
    }

    if ($paymentReference->status !== PaymentStatus::Pending) {
      $this->info(
        "Payment reference {$reference} is already {$paymentReference->status->value} — nothing to do."
      );

      return self::SUCCESS;
    }

    $fee = $paymentReference->getPaymentable();
    if (!($fee instanceof Fee)) {
      $this->error(
        'Payment reference is not for a Fee — refusing to simulate.'
      );

      return self::FAILURE;
    }

    $payable = $paymentReference->getPayable();

    $paymentReference->confirmPayment();

    [$receipt] = FeePaymentHandler::make(
      $paymentReference->getInstitution()
    )->create(
      [
        'reference' => $paymentReference->getReference(),
        'user_id' => $payable?->id ?? $paymentReference->getUser()?->id,
        'amount' => $paymentReference->getAmount(),
        'method' => $paymentReference->getPaymentMethod()->value
      ],
      $fee,
      $payable,
      null,
      allowOverPayment: true
    );

    $this->info(
      "Simulated online payment completion for {$reference}: receipt #{$receipt->id} now {$receipt->status->value} (paid {$receipt->amount_paid} of {$receipt->amount})."
    );

    return self::SUCCESS;
  }
}
