<?php

namespace App\Services\Messaging\Whatsapp;

class WhatsappIntentResolver
{
  public const CHECK_RESULT = 'check_result';
  public const MENU = 'menu';
  public const RESET = 'reset';
  public const UNKNOWN = 'unknown';

  public function resolve(?string $message): string
  {
    $text = $this->normalize($message);
    if ($text === '') {
      return self::UNKNOWN;
    }

    if (in_array($text, ['cancel', 'stop', 'menu', 'start', 'help'], true)) {
      return self::RESET;
    }

    if ($text === '1') {
      return self::CHECK_RESULT;
    }

    $resultPhrases = [
      'check result',
      'my result',
      'see result',
      'show result',
      'view result',
      'check my child result',
      'check my ward result',
      'i want to check result',
      'i want to see my result',
      'result checker',
      'report card',
      'check report card',
      'my childs result',
      'my child result',
      'student result'
    ];

    if (in_array($text, $resultPhrases, true)) {
      return self::CHECK_RESULT;
    }

    if (str_contains($text, 'result') || str_contains($text, 'report card')) {
      return self::CHECK_RESULT;
    }

    return self::UNKNOWN;
  }

  private function normalize(?string $message): string
  {
    $text = strtolower(trim($message ?? ''));
    $text = str_replace(["'", '’'], '', $text);
    $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

    return trim(preg_replace('/\s+/', ' ', $text) ?? '');
  }
}
