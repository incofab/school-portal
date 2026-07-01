<?php

namespace App\Services\Messaging\Whatsapp;

use Illuminate\Support\Facades\Cache;

class WhatsappConversationStateService
{
  public const FLOW_CHECK_RESULT = 'check_result';
  public const STEP_SELECT_STUDENT = 'select_student';
  public const STEP_SELECT_RESULT = 'select_result';
  public const STEP_ENTER_ACTIVATION_PIN = 'enter_activation_pin';

  public function __construct(private PhoneNumberNormalizer $normalizer)
  {
  }

  public function putStudentSelection(string $phone, array $studentIds): void
  {
    $this->put($phone, self::STEP_SELECT_STUDENT, [
      'student_ids' => array_values($studentIds)
    ]);
  }

  public function putResultSelection(
    string $phone,
    int $studentId,
    array $termResultIds
  ): void {
    $this->put($phone, self::STEP_SELECT_RESULT, [
      'student_id' => $studentId,
      'term_result_ids' => array_values($termResultIds)
    ]);
  }

  public function putActivationPinPrompt(
    string $phone,
    int $studentId,
    array $termResultIds
  ): void {
    $this->put($phone, self::STEP_ENTER_ACTIVATION_PIN, [
      'student_id' => $studentId,
      'term_result_ids' => array_values($termResultIds)
    ]);
  }

  private function put(string $phone, string $step, array $data): void
  {
    Cache::put(
      $this->key($phone),
      [
        'flow' => self::FLOW_CHECK_RESULT,
        'step' => $step,
        ...$data,
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
    if (
      ($state['flow'] ?? null) !== self::FLOW_CHECK_RESULT ||
      ($state['step'] ?? self::STEP_SELECT_STUDENT) !==
        self::STEP_SELECT_STUDENT
    ) {
      return null;
    }

    $selection = (int) trim($message);
    $studentIds = $state['student_ids'] ?? [];

    return $selection > 0 && isset($studentIds[$selection - 1])
      ? (int) $studentIds[$selection - 1]
      : null;
  }

  public function selectedTermResultIds(string $phone, string $message): ?array
  {
    $state = $this->get($phone);
    if (
      ($state['flow'] ?? null) !== self::FLOW_CHECK_RESULT ||
      ($state['step'] ?? null) !== self::STEP_SELECT_RESULT
    ) {
      return null;
    }

    $selection = (int) trim($message);
    $termResultIds = $state['term_result_ids'] ?? [];

    return $selection > 0 && isset($termResultIds[$selection - 1])
      ? [(int) $termResultIds[$selection - 1]]
      : null;
  }

  private function key(string $phone): string
  {
    return 'whatsapp:conversation:' .
      ($this->normalizer->normalize($phone) ?? $phone);
  }
}
