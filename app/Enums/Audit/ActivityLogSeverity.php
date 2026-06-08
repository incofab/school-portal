<?php

namespace App\Enums\Audit;

use App\Traits\EnumToArray;

enum ActivityLogSeverity: string
{
    use EnumToArray;

    case Info = 'info';
    case Notice = 'notice';
    case Security = 'security';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
}
