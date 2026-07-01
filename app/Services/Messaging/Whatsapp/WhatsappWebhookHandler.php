<?php

namespace App\Services\Messaging\Whatsapp;

use App\Models\Student;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookHandler
{
  public function __construct(
    private WhatsappIntentResolver $intentResolver,
    private WhatsappConversationStateService $state,
    private WhatsappStudentResolver $studentResolver,
    private WhatsappResultService $resultService,
    private WhatsappCloudApiService $sender
  ) {
  }

  public function handle(array $payload): void
  {
    $contactNames = $this->contactNames($payload);

    foreach ($this->messages($payload) as $message) {
      $messageId = $message['id'] ?? null;
      if (
        $messageId &&
        !Cache::add(
          "whatsapp:webhook:message:$messageId",
          true,
          now()->addDay()
        )
      ) {
        Log::info('Skipping duplicate WhatsApp webhook message.', [
          'message_id' => $messageId
        ]);
        continue;
      }

      $from = $message['from'] ?? null;
      if (!$from) {
        continue;
      }

      $senderName = $contactNames[$from] ?? null;
      $text = $this->messageText($message);
      if (!$text) {
        $this->sender->sendTextMessage($from, $this->menuMessage($senderName));
        continue;
      }

      $this->handleTextMessage($from, $text, $senderName);
    }
  }

  private function handleTextMessage(
    string $from,
    string $text,
    ?string $senderName
  ): void {
    $intent = $this->intentResolver->resolve($text);

    if ($intent === WhatsappIntentResolver::RESET) {
      $this->state->clear($from);
      $this->sender->sendTextMessage($from, $this->menuMessage($senderName));
      return;
    }

    $state = $this->state->get($from);
    if ($state) {
      match (
      $state['step'] ?? WhatsappConversationStateService::STEP_SELECT_STUDENT
      ) {
        WhatsappConversationStateService::STEP_SELECT_RESULT
          => $this->handleResultSelection($from, $text, $state, $senderName),
        WhatsappConversationStateService::STEP_ENTER_ACTIVATION_PIN
          => $this->handleActivationPin($from, $text, $state, $senderName),
        default => $this->handleStudentSelection($from, $text, $senderName)
      };
      return;
    }

    if ($intent === WhatsappIntentResolver::CHECK_RESULT) {
      $this->startCheckResult($from, $senderName);
      return;
    }

    $this->sender->sendTextMessage($from, $this->menuMessage($senderName));
  }

  private function handleStudentSelection(
    string $from,
    string $text,
    ?string $senderName
  ): void {
    $studentId = $this->state->selectedStudentId($from, $text);
    if (!$studentId) {
      $state = $this->state->get($from);
      $students = Student::query()
        ->with('user', 'classification', 'institutionUser.institution')
        ->whereIn('id', $state['student_ids'] ?? [])
        ->get();

      $this->sender->sendTextMessage(
        $from,
        "Please reply with a valid student number.\n\n" .
          $this->studentSelectionMessage($students)
      );
      return;
    }

    $student = Student::query()
      ->with(
        'user',
        'classification',
        'institutionUser.institution.institutionSettings'
      )
      ->find($studentId);

    if (!$student) {
      $this->state->clear($from);
      $this->sender->sendTextMessage(
        $from,
        'We could not find that student anymore. Please try again.'
      );
      return;
    }

    $this->state->clear($from);
    $this->sendResultResponse($from, $student, $senderName);
  }

  private function handleResultSelection(
    string $from,
    string $text,
    array $state,
    ?string $senderName
  ): void {
    $termResultIds = $this->state->selectedTermResultIds($from, $text);
    if (!$termResultIds) {
      $student = $this->findStudent($state['student_id'] ?? null);
      $results = $student
        ? $this->resultService->buildCurrentResultResponse(
          $student,
          $senderName
        )
        : null;

      $this->sender->sendTextMessage(
        $from,
        "Please reply with a valid result number.\n\n" .
          ($results?->message ?? 'Please start again by typing Check Result.')
      );
      return;
    }

    $student = $this->findStudent($state['student_id'] ?? null);
    if (!$student) {
      $this->state->clear($from);
      $this->sender->sendTextMessage(
        $from,
        'We could not find that student anymore. Please try again.'
      );
      return;
    }

    $this->state->clear($from);
    $response = $this->resultService->buildSelectedResultResponse(
      $student,
      $termResultIds,
      $senderName
    );
    $this->sendResponse($from, $response);
  }

  private function handleActivationPin(
    string $from,
    string $text,
    array $state,
    ?string $senderName
  ): void {
    $student = $this->findStudent($state['student_id'] ?? null);
    if (!$student) {
      $this->state->clear($from);
      $this->sender->sendTextMessage(
        $from,
        'We could not find that student anymore. Please try again.'
      );
      return;
    }

    $this->state->clear($from);
    $response = $this->resultService->activateAndBuildResponse(
      $student,
      $state['term_result_ids'] ?? [],
      trim($text),
      $senderName
    );
    $this->sendResponse($from, $response);
  }

  private function startCheckResult(string $from, ?string $senderName): void
  {
    $students = $this->studentResolver->resolve($from);

    if ($students->isEmpty()) {
      $this->sender->sendTextMessage(
        $from,
        'We could not find any student linked to this WhatsApp number. Please contact your school administrator or use the phone number registered with the school.'
      );
      return;
    }

    if ($students->count() === 1) {
      $this->sendResultResponse($from, $students->first(), $senderName);
      return;
    }

    $this->state->putStudentSelection($from, $students->pluck('id')->all());
    $this->sender->sendTextMessage(
      $from,
      $this->studentSelectionMessage($students)
    );
  }

  private function sendResultResponse(
    string $from,
    Student $student,
    ?string $senderName
  ): void {
    $response = $this->resultService->buildCurrentResultResponse(
      $student,
      $senderName
    );
    $this->sendResponse($from, $response);
  }

  private function sendResponse(
    string $from,
    WhatsappResultResponse $response
  ): void {
    if (
      $response->state === WhatsappConversationStateService::STEP_SELECT_RESULT
    ) {
      $this->state->putResultSelection(
        $from,
        (int) $response->stateData['student_id'],
        $response->stateData['term_result_ids'] ?? []
      );
    }

    if (
      $response->state ===
      WhatsappConversationStateService::STEP_ENTER_ACTIVATION_PIN
    ) {
      $this->state->putActivationPinPrompt(
        $from,
        (int) $response->stateData['student_id'],
        $response->stateData['term_result_ids'] ?? []
      );
    }

    $this->sender->sendTextMessage($from, $response->message);
  }

  private function findStudent($studentId): ?Student
  {
    if (!$studentId) {
      return null;
    }

    return Student::query()
      ->with(
        'user',
        'classification',
        'institutionUser.institution.institutionSettings'
      )
      ->find($studentId);
  }

  private function menuMessage(?string $senderName = null): string
  {
    $message =
      "Welcome. Please choose an option:\n\n1. Check Result\n\nReply with 1 or type 'Check Result'.";
    $name = trim((string) $senderName);

    return $name === '' ? $message : "Hi {$name}, " . $message;
  }

  private function studentSelectionMessage($students): string
  {
    $lines = [
      'We found more than one student linked to this WhatsApp number. Please choose the student whose result you want to check:',
      ''
    ];

    foreach ($students->values() as $index => $student) {
      $lines[] =
        $index +
        1 .
        '. ' .
        ($student->user?->full_name ?? 'Student') .
        ' - ' .
        ($student->classification?->title ?? 'N/A') .
        ' - ' .
        ($student->institutionUser?->institution?->name ?? 'N/A');
    }

    $lines[] = '';
    $lines[] = 'Reply with the number of the student.';

    return implode("\n", $lines);
  }

  private function messageText(array $message): ?string
  {
    return match ($message['type'] ?? null) {
      'text' => $message['text']['body'] ?? null,
      'button' => $message['button']['text'] ??
        ($message['button']['payload'] ?? null),
      'interactive' => $message['interactive']['button_reply']['title'] ??
        ($message['interactive']['list_reply']['title'] ?? null),
      default => null
    };
  }

  private function messages(array $payload): array
  {
    $messages = [];

    foreach ($payload['entry'] ?? [] as $entry) {
      foreach ($entry['changes'] ?? [] as $change) {
        foreach ($change['value']['messages'] ?? [] as $message) {
          $messages[] = $message;
        }
      }
    }

    return $messages;
  }

  private function contactNames(array $payload): array
  {
    $contacts = [];

    foreach ($payload['entry'] ?? [] as $entry) {
      foreach ($entry['changes'] ?? [] as $change) {
        foreach ($change['value']['contacts'] ?? [] as $contact) {
          $waId = $contact['wa_id'] ?? null;
          $name = $contact['profile']['name'] ?? null;
          if ($waId && $name) {
            $contacts[$waId] = $name;
          }
        }
      }
    }

    return $contacts;
  }
}
