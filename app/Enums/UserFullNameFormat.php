<?php

namespace App\Enums;

use App\Models\User;
use App\Traits\EnumToArray;

enum UserFullNameFormat: string
{
  use EnumToArray;

  case FirstOtherLast = 'first-other-last';
  case FirstLastOther = 'first-last-other';
  case LastFirstOther = 'last-first-other';
  case LastOtherFirst = 'last-other-first';
  case OtherFirstLast = 'other-first-last';
  case OtherLastFirst = 'other-last-first';

  public function format(User $user): string
  {
    return collect(match ($this) {
      self::FirstOtherLast => [
        $user->first_name,
        $user->other_names,
        $user->last_name,
      ],
      self::FirstLastOther => [
        $user->first_name,
        $user->last_name,
        $user->other_names,
      ],
      self::LastFirstOther => [
        $user->last_name,
        $user->first_name,
        $user->other_names,
      ],
      self::LastOtherFirst => [
        $user->last_name,
        $user->other_names,
        $user->first_name,
      ],
      self::OtherFirstLast => [
        $user->other_names,
        $user->first_name,
        $user->last_name,
      ],
      self::OtherLastFirst => [
        $user->other_names,
        $user->last_name,
        $user->first_name,
      ],
    })
      ->filter(fn(?string $value) => filled($value))
      ->implode(' ');
  }
}
