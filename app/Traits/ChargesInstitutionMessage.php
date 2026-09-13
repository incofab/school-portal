<?php

namespace App\Traits;

use App\Actions\Messages\ApplyMessageCharges;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\Message;

trait ChargesInstitutionMessage
{
    protected function chargeInstitutionMessage(
        Institution $institution,
        NotificationChannelsType|string $channel,
        int $recipientCount,
        ?Message $messageModel,
        string $reference
    ): bool {
        $res = ApplyMessageCharges::make($institution)->runForCount(
            $recipientCount,
            $channel,
            $messageModel,
            $reference
        );

        if ($res->isSuccessful()) {
            return true;
        }

        $messageModel
            ?->fill(['status' => MessageStatus::Failed->value])
            ->save();

        return false;
    }
}
