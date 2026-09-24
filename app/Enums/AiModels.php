<?php

namespace App\Enums;

enum AiModels: string
{
  case OPENAI_SMALL = 'gpt-4o-mini';
  case OPENAI_MEDIUM = 'gpt-5.4-nano';
  case OPENAI_LARGE = 'gpt-5.6-luna';

  static function small(): string
  {
    return self::OPENAI_SMALL->value;
  }

  static function medium(): string
  {
    return self::OPENAI_MEDIUM->value;
  }

  static function large(): string
  {
    return self::OPENAI_LARGE->value;
  }
}
