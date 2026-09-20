<?php

namespace App\Services\Messaging;

use App\Models\Institution;
use App\Services\Messaging\Whatsapp\PhoneNumberNormalizer;
use App\Support\Res;

class BulkSmsCompliance
{
  public function __construct(
    private ?array $blockedWords = null,
    private ?array $blockedSenderIds = null,
    private ?array $blockedCharacters = null,
    private ?int $maxSenderLength = null,
    private ?int $maxMessageLength = null,
    private ?PhoneNumberNormalizer $phoneNumberNormalizer = null
  ) {
    $this->blockedWords ??= config('bulksms_nigeria.blocked_words', []);
    $this->blockedSenderIds ??= config(
      'bulksms_nigeria.blocked_sender_ids',
      []
    );
    $this->blockedCharacters ??= config(
      'bulksms_nigeria.blocked_characters',
      []
    );
    $this->maxSenderLength ??= (int) config(
      'bulksms_nigeria.max_sender_length',
      11
    );
    $this->maxMessageLength ??= (int) config(
      'bulksms_nigeria.max_message_length',
      1530
    );
    $this->phoneNumberNormalizer ??= new PhoneNumberNormalizer();
  }

  public function senderId(?Institution $institution = null): string
  {
    return trim(
      (string) config('services.bulksms_nigeria.sender-id', 'EduManager')
    );
  }

  public function validateMessageAndSender(
    string $message,
    string $senderId
  ): Res {
    $violations = [];
    $blockedWords = $this->findBlockedWords($message);

    if ($blockedWords) {
      $violations['blocked_words'] = $blockedWords;
    }

    $blockedCharacters = array_values(
      array_filter(
        $this->blockedCharacters,
        fn(string $character) => str_contains($message, $character)
      )
    );
    if ($blockedCharacters) {
      $violations['blocked_characters'] = $blockedCharacters;
    }

    if (trim($message) === '') {
      $violations['message'] = 'Message body is required.';
    } elseif (mb_strlen($message) > $this->maxMessageLength) {
      $violations[
        'message'
      ] = "Message body must not exceed {$this->maxMessageLength} characters.";
    }

    if (!preg_match('/^[A-Za-z]{3,11}$/', $senderId)) {
      $violations['sender_id'] =
        'Sender ID must contain 3 to 11 letters and no special characters or numbers.';
    } elseif (mb_strlen($senderId) > $this->maxSenderLength) {
      $violations[
        'sender_id'
      ] = "Sender ID must not exceed {$this->maxSenderLength} characters.";
    }

    if ($this->isBlockedSenderId($senderId)) {
      $violations['blocked_sender_id'] = $senderId;
    }

    if ($violations) {
      return failRes('SMS failed provider compliance checks.', [
        'violations' => $violations
      ]);
    }

    return successRes();
  }

  public function validate(
    string $message,
    string $senderId,
    string $recipients
  ): Res {
    $contentResult = $this->validateMessageAndSender($message, $senderId);
    if ($contentResult->isNotSuccessful()) {
      return $contentResult;
    }

    $normalizedRecipients = [];
    $invalidRecipients = [];
    foreach (explode(',', $recipients) as $recipient) {
      $recipient = trim($recipient);
      if ($recipient === '') {
        continue;
      }

      $normalized = $this->phoneNumberNormalizer->normalize($recipient);
      if (!$normalized || !preg_match('/^234[789][01]\d{8}$/', $normalized)) {
        $invalidRecipients[] = $recipient;
        continue;
      }

      $normalizedRecipients[] = $normalized;
    }

    if (!$normalizedRecipients || $invalidRecipients) {
      return failRes('SMS failed provider compliance checks.', [
        'violations' => [
          'recipients' => $invalidRecipients ?: ['No valid recipients found.']
        ]
      ]);
    }

    return successRes('', [
      'sender_id' => $senderId,
      'recipients' => implode(',', array_unique($normalizedRecipients))
    ]);
  }

  private function findBlockedWords(string $message): array
  {
    $normalizedMessage = $this->normalizeForMatching($message);
    $blockedWords = [];

    foreach ($this->blockedWords as $blockedWord) {
      $normalizedWord = $this->normalizeForMatching($blockedWord);
      if (
        $normalizedWord !== '' &&
        str_contains(" {$normalizedMessage} ", " {$normalizedWord} ")
      ) {
        $blockedWords[] = $blockedWord;
      }
    }

    return array_values(array_unique($blockedWords));
  }

  private function isBlockedSenderId(string $senderId): bool
  {
    $normalizedSenderId = $this->normalizeForMatching($senderId);

    foreach ($this->blockedSenderIds as $blockedSenderId) {
      if (
        $normalizedSenderId === $this->normalizeForMatching($blockedSenderId)
      ) {
        return true;
      }
    }

    return false;
  }

  private function normalizeForMatching(string $value): string
  {
    $value = strtolower($value);
    $value = strtr($value, [
      '0' => 'o',
      '1' => 'i',
      '3' => 'e',
      '4' => 'a',
      '5' => 's',
      '7' => 't'
    ]);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
  }
}
