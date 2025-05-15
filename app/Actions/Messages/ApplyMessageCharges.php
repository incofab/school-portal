<?php
namespace App\Actions\Messages;

use App\Enums\NotificationChannelsType;
use App\Enums\PriceLists\PriceType;
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
    $instGroupPriceList = $institutionGroup
      ->pricelists()
      ->where(
        'type',
        $channel === NotificationChannelsType::Sms->value
          ? PriceType::SmsSending->value
          : PriceType::EmailSending->value
      )
      ->first();

    if (!$instGroupPriceList) {
      return failRes('Price List has not been set');
    }

    $amountToPay = $receivers->count() * $instGroupPriceList->amount;

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
