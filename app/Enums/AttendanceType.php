<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum AttendanceType: string
{
    use EnumToArray;

    case In = 'in';
    case Out = 'out';

    // public static function values(): array
    // {
    //     return [
    //         self::In->value,
    //         self::Out->value,
    //     ];
    // }
}
