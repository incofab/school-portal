<?php

namespace App\Enums\PriceLists;

enum PriceType: string
{
    case ResultChecking = 'result-checking';
    case EmailSending = 'email-sending';
    case SmsSending = 'sms-sending';
}