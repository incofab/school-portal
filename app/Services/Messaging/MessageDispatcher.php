<?php

namespace App\Services\Messaging;

use App\Enums\NotificationChannelsType;
use App\Jobs\SendBulksms;
use App\Jobs\SendWhatsappMessage;
use App\Mail\InstitutionMessageMail;
use App\Models\Institution;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

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
  ): void {
    $channelType =
      $channel instanceof NotificationChannelsType
        ? $channel
        : NotificationChannelsType::from($channel);

    match ($channelType) {
      NotificationChannelsType::Sms => $this->dispatchSms(
        $receivers,
        $message,
        $messageModel
      ),
      NotificationChannelsType::Whatsapp => $this->dispatchWhatsapp(
        $messageModel,
        $context
      ),
      default => $this->dispatchEmail(
        $receivers,
        $subject ?? 'Generic Message',
        $message,
        $messageModel
      )
    };
  }

  private function dispatchSms(
    Collection $receivers,
    string $message,
    ?Message $messageModel
  ): void {
    SendBulksms::dispatch(
      $message,
      $receivers->join(','),
      $messageModel,
      $this->institution
    );
  }

  private function dispatchEmail(
    Collection $receivers,
    string $subject,
    string $message,
    ?Message $messageModel
  ): void {
    Mail::to($receivers->toArray())->queue(
      new InstitutionMessageMail(
        $this->institution,
        $subject,
        $message,
        $messageModel
      )
    );
  }

  private function dispatchWhatsapp(
    ?Message $messageModel,
    array $multiplePayload
  ): void {
    SendWhatsappMessage::dispatch(
      multiplePayload: $multiplePayload,
      messageModel: $messageModel
    );
  }
}
