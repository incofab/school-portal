<?php
namespace App\Actions\Messages;

use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\Message;
use App\Support\Res;
use App\Support\TransactionHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApplyMessageCharges
{
  function __construct(private Institution $institution)
  {
  }

  static function make(Institution $institution): self
  {
    return new self($institution);
  }

  function run(Collection $receivers, $channel, Message $messageModel): Res
  {
    $institutionGroup = $this->institution->institutionGroup;
    $charge =
      $channel === NotificationChannelsType::Sms->value
        ? config('services.sms-charge')
        : config('services.email-charge');
    $amountToPay = $receivers->count() * $charge;

    if ($amountToPay > $institutionGroup->credit_wallet) {
      return failRes('Insufficient wallet balance');
    }

    if ($amountToPay > 0) {
      TransactionHandler::make(
        $this->institution,
        Str::orderedUuid()
      )->deductCreditWallet(
        $amountToPay,
        $messageModel,
        "Sent {$receivers->count()} $channel message(s)"
      );
    }
    return successRes();
  }
}
