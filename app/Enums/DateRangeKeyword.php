<?php

namespace App\Enums;

use App\Traits\EnumToArray;
use Carbon\Carbon;

enum DateRangeKeyword: string
{
  use EnumToArray;

  case Today = 'today';
  case Yesterday = 'yesterday';
  case Tomorrow = 'tomorrow';
  case ThisWeek = 'this_week';
  case LastWeek = 'last_week';
  case NextWeek = 'next_week';
  case ThisMonth = 'this_month';
  case LastMonth = 'last_month';
  case NextMonth = 'next_month';
  case ThisQuarter = 'this_quarter';
  case LastQuarter = 'last_quarter';
  case NextQuarter = 'next_quarter';
  case ThisYear = 'this_year';
  case LastYear = 'last_year';
  case NextYear = 'next_year';

  public function resolveDateRange(): array
  {
    $date = Carbon::now();

    return match ($this) {
      self::Today => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
      self::Yesterday => [
        $date->copy()->subDay()->startOfDay(),
        $date->copy()->subDay()->endOfDay()
      ],
      self::Tomorrow => [
        $date->copy()->addDay()->startOfDay(),
        $date->copy()->addDay()->endOfDay()
      ],
      self::ThisWeek => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
      self::LastWeek => [
        $date->copy()->subWeek()->startOfWeek(),
        $date->copy()->subWeek()->endOfWeek()
      ],
      self::NextWeek => [
        $date->copy()->addWeek()->startOfWeek(),
        $date->copy()->addWeek()->endOfWeek()
      ],
      self::ThisMonth => [
        $date->copy()->startOfMonth(),
        $date->copy()->endOfMonth()
      ],
      self::LastMonth => [
        $date->copy()->subMonth()->startOfMonth(),
        $date->copy()->subMonth()->endOfMonth()
      ],
      self::NextMonth => [
        $date->copy()->addMonth()->startOfMonth(),
        $date->copy()->addMonth()->endOfMonth()
      ],
      self::ThisQuarter => [
        $date->copy()->startOfQuarter(),
        $date->copy()->endOfQuarter()
      ],
      self::LastQuarter => [
        $date->copy()->subQuarter()->startOfQuarter(),
        $date->copy()->subQuarter()->endOfQuarter()
      ],
      self::NextQuarter => [
        $date->copy()->addQuarter()->startOfQuarter(),
        $date->copy()->addQuarter()->endOfQuarter()
      ],
      self::ThisYear => [$date->copy()->startOfYear(), $date->copy()->endOfYear()],
      self::LastYear => [
        $date->copy()->subYear()->startOfYear(),
        $date->copy()->subYear()->endOfYear()
      ],
      self::NextYear => [
        $date->copy()->addYear()->startOfYear(),
        $date->copy()->addYear()->endOfYear()
      ]
    };
  }
}
