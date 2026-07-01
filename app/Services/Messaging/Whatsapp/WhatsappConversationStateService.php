<?php

namespace App\Services\Messaging\Whatsapp;

use Illuminate\Support\Facades\Cache;

class WhatsappConversationStateService
{
  public function __construct(private PhoneNumberNormalizer $normalizer)
  {
  }

  public function putStudentSelection(string $phone, array $studentIds): void
  {
    Cache::put(
      $this->key($phone),
      [
        'flow' => 'check_result',
        'student_ids' => array_values($studentIds),
        'created_at' => now()->toISOString()
      ],
      now()->addMinutes(30)
    );
  }

  public function get(string $phone): ?array
  {
    $state = Cache::get($this->key($phone));

    return is_array($state) ? $state : null;
  }

  public function clear(string $phone): void
  {
    Cache::forget($this->key($phone));
  }

  public function selectedStudentId(string $phone, string $message): ?int
  {
    $state = $this->get($phone);
    if (($state['flow'] ?? null) !== 'check_result') {
      return null;
    }

    $selection = (int) trim($message);
    $studentIds = $state['student_ids'] ?? [];

    return $selection > 0 && isset($studentIds[$selection - 1])
      ? (int) $studentIds[$selection - 1]
      : null;
  }

  private function key(string $phone): string
  {
    return 'whatsapp:conversation:' .
      ($this->normalizer->normalize($phone) ?? $phone);
  }
}
