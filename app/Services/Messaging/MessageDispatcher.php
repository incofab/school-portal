<?php

namespace App\Services\Messaging;

use App\Actions\Messages\ApplyMessageCharges;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Jobs\SendBulksms;
use App\Jobs\SendWhatsappTemplateMessage;
use App\Mail\InstitutionMessageMail;
use App\Models\Institution;
use App\Models\Message;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplateUtility;
use App\Support\Res;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MessageDispatcher
{
  public function __construct(private Institution $institution)
  {
  }

  public function dispatch(
    Collection $receivers,
    NotificationChannelsType|string $channel,
    string $message,
    ?string $subject = null,
    ?Message $messageModel = null,
    array $context = []
  ): Res {
    $channelType =
      $channel instanceof NotificationChannelsType
        ? $channel
        : NotificationChannelsType::from($channel);

    $chargeReference = Str::orderedUuid()->toString();
    if (
      in_array(
        $channelType,
        [NotificationChannelsType::Sms, NotificationChannelsType::Email],
        true
      )
    ) {
      $charge = ApplyMessageCharges::make($this->institution)->run(
        $receivers,
        $channelType,
        $messageModel,
        $chargeReference
      );

      if ($charge->isNotSuccessful()) {
        $messageModel
          ?->fill(['status' => MessageStatus::Failed->value])
          ->save();

        return $charge;
      }
    }

    match ($channelType) {
      NotificationChannelsType::Sms => $this->dispatchSms(
        $receivers,
        $message,
        $messageModel,
        $chargeReference
      ),
      NotificationChannelsType::Whatsapp => $this->dispatchWhatsapp(
        $receivers,
        $subject ?? '',
        $message,
        $messageModel
      ),
      default => $this->dispatchEmail(
        $receivers,
        $subject ?? 'Generic Message',
        $message,
        $messageModel,
        $chargeReference
      )
    };

    return successRes();
  }

  private function dispatchSms(
    Collection $receivers,
    string $message,
    ?Message $messageModel,
    string $chargeReference
  ): void {
    SendBulksms::dispatch(
      $message,
      $receivers->join(','),
      $messageModel,
      $this->institution,
      $receivers->count(),
      $chargeReference
    );
  }

  private function dispatchEmail(
    Collection $receivers,
    string $subject,
    string $message,
    ?Message $messageModel,
    string $chargeReference
  ): void {
    Mail::to($receivers->toArray())->queue(
      new InstitutionMessageMail(
        $this->institution,
        $subject,
        $message,
        $messageModel,
        $receivers->count(),
        $chargeReference
      )
    );
  }

  private function dispatchWhatsapp(
    Collection $receivers,
    string $subject,
    string $message,
    ?Message $messageModel
  ): void {
    foreach ($receivers as $receiver) {
      SendWhatsappTemplateMessage::dispatch(
        whatsappTemplate: new WhatsappTemplateUtility(
          $receiver,
          $this->institution->name,
          "|$subject|",
          $message
        ),
        messageModel: $messageModel
      );
    }
  }
}
