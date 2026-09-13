<?php

namespace App\Actions\Messages;

use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Support\Res;
use App\Support\TransactionHandler;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApplyMessageCharges
{
  public function __construct(private Institution $institution)
  {
  }

  public static function make(Institution $institution): self
  {
    return new self($institution);
  }

  public function run(
    Collection $receivers,
    $channel,
    ?Model $transactionable = null,
    ?string $reference = null
  ): Res {
    return $this->runForCount(
      $receivers->count(),
      $channel,
      $transactionable,
      $reference
    );
  }

  /**
   * The reference is supplied by the dispatcher and reused by the worker so
   * retries are treated as the same wallet debit.
   */
  public function runForCount(
    int $receiverCount,
    $channel,
    ?Model $transactionable = null,
    ?string $reference = null
  ): Res {
    $channel =
      $channel instanceof NotificationChannelsType ? $channel->value : $channel;
    $charge = match ($channel) {
      NotificationChannelsType::Sms->value => config('services.sms-charge'),
      NotificationChannelsType::Whatsapp->value => config(
        'services.whatsapp-charge'
      ),
      default => config('services.email-charge')
    };
    $charge = max(0, (float) $charge);
    $amountToPay = $receiverCount * $charge;

    if ($amountToPay <= 0) {
      return successRes();
    }

    try {
      TransactionHandler::make(
        $this->institution,
        $reference ?? Str::orderedUuid()->toString()
      )->deductCreditWallet(
        $amountToPay,
        $transactionable ?? $this->institution,
        "Sent {$receiverCount} {$channel} message(s)"
      );
    } catch (Exception $exception) {
      return failRes($exception->getMessage());
    }
    return successRes();
  }
}
