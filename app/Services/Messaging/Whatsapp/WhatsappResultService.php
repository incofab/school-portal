<?php

namespace App\Services\Messaging\Whatsapp;

use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\TermResult;
use App\Services\Results\TermResultAccessService;
use App\Support\SettingsHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class WhatsappResultService
{
  public function __construct(private TermResultAccessService $resultAccess)
  {
  }

  public function buildCurrentResultResponse(
    Student $student,
    ?string $senderName = null
  ): WhatsappResultResponse {
    $student->loadMissing(
      'user',
      'classification',
      'institutionUser.institution.institutionSettings'
    );

    $institution = $student->institutionUser?->institution;
    if (!$institution) {
      return new WhatsappResultResponse(
        $this->withGreeting(
          'We could not identify the school for this student. Please contact your school administrator.',
          $senderName
        )
      );
    }

    $settings = SettingsHandler::makeFromInstitution($institution);
    $academicSessionId = $settings->getCurrentAcademicSession(null);
    $term = $settings->getCurrentTerm(null);

    if (!$academicSessionId || !$term) {
      return new WhatsappResultResponse(
        $this->withGreeting(
          'We could not find the current academic term/session for your school. Please contact your school.',
          $senderName
        )
      );
    }

    $termResults = $this->resultAccess->resultsForTerm(
      $student,
      (int) $academicSessionId,
      (string) $term
    );

    if ($termResults->isEmpty()) {
      return $this->missingCurrentResultResponse(
        $student,
        (string) $term,
        (int) $academicSessionId,
        $senderName
      );
    }

    return $this->buildResultResponseForResults(
      $student,
      $termResults,
      $senderName
    );
  }

  public function buildSelectedResultResponse(
    Student $student,
    array $termResultIds,
    ?string $senderName = null
  ): WhatsappResultResponse {
    $termResults = $this->resultAccess->resultsByIds($student, $termResultIds);
    if ($termResults->isEmpty()) {
      return new WhatsappResultResponse(
        $this->withGreeting(
          'We could not find that result anymore. Please check result again.',
          $senderName
        )
      );
    }

    return $this->buildResultResponseForResults(
      $student,
      $termResults,
      $senderName
    );
  }

  public function activateAndBuildResponse(
    Student $student,
    array $termResultIds,
    string $pinValue,
    ?string $senderName = null
  ): WhatsappResultResponse {
    $pin = $this->resultAccess->findPin($pinValue);
    if (!$pin) {
      return new WhatsappResultResponse(
        $this->withGreeting(
          'Invalid activation pin. Please enter a valid activation pin.',
          $senderName
        ),
        false,
        WhatsappConversationStateService::STEP_ENTER_ACTIVATION_PIN,
        [
          'student_id' => $student->id,
          'term_result_ids' => array_values($termResultIds)
        ]
      );
    }

    $termResults = $this->resultAccess->resultsByIds($student, $termResultIds);
    try {
      foreach ($termResults as $termResult) {
        if (!$termResult->isActivated()) {
          $this->resultAccess->activate($termResult, $pin, $student);
        }
      }
    } catch (ValidationException $exception) {
      return new WhatsappResultResponse(
        $this->withGreeting(
          collect($exception->errors())
            ->flatten()
            ->first() ?? 'Invalid activation pin. Please try again.',
          $senderName
        ),
        false,
        WhatsappConversationStateService::STEP_ENTER_ACTIVATION_PIN,
        [
          'student_id' => $student->id,
          'term_result_ids' => array_values($termResultIds)
        ]
      );
    }

    return $this->buildSelectedResultResponse(
      $student,
      $termResultIds,
      $senderName
    );
  }

  private function buildResultResponseForResults(
    Student $student,
    Collection $termResults,
    ?string $senderName
  ): WhatsappResultResponse {
    if (
      $termResults->contains(fn(TermResult $result) => !$result->isPublished())
    ) {
      return new WhatsappResultResponse(
        $this->withGreeting(
          'Your result is not yet published. Please check again later or contact your school.',
          $senderName
        )
      );
    }

    $student->loadMissing('institutionUser.institution.institutionSettings');
    $institution = $student->institutionUser->institution;
    if (
      $this->resultAccess->institutionRequiresActivation($institution) &&
      $termResults->contains(fn(TermResult $result) => !$result->isActivated())
    ) {
      return new WhatsappResultResponse(
        $this->withGreeting(
          'Your result is available, but it has not been activated. Please enter your result activation pin.',
          $senderName
        ),
        false,
        WhatsappConversationStateService::STEP_ENTER_ACTIVATION_PIN,
        [
          'student_id' => $student->id,
          'term_result_ids' => $termResults->pluck('id')->all()
        ]
      );
    }

    return new WhatsappResultResponse(
      $this->availableMessage($termResults, $senderName),
      true
    );
  }

  private function missingCurrentResultResponse(
    Student $student,
    string $term,
    int $academicSessionId,
    ?string $senderName
  ): WhatsappResultResponse {
    $availableResults = $this->resultAccess
      ->latestAvailableResults($student)
      ->reject(
        fn(TermResult $result) => $result->academic_session_id ===
          $academicSessionId &&
          ($result->term?->value ?? $result->term) === $term
      )
      ->values();

    $academicSessionTitle =
      $availableResults->firstWhere('academic_session_id', $academicSessionId)
        ?->academicSession?->title ??
      (AcademicSession::query()->find($academicSessionId)?->title ??
        'selected');

    $message = sprintf(
      'We could not find a result for the %s %s session yet.',
      $this->termLabel($term),
      $academicSessionTitle
    );

    if ($availableResults->isEmpty()) {
      return new WhatsappResultResponse(
        $this->withGreeting($message, $senderName)
      );
    }

    $lines = [
      $message,
      '',
      'Available recent results:',
      ...$availableResults
        ->values()
        ->map(
          fn(TermResult $result, int $index) => $index +
            1 .
            '. ' .
            $this->resultLabel($result)
        )
        ->all(),
      '',
      'Reply with the number of the result you want to check.'
    ];

    return new WhatsappResultResponse(
      $this->withGreeting(implode("\n", $lines), $senderName),
      false,
      WhatsappConversationStateService::STEP_SELECT_RESULT,
      [
        'student_id' => $student->id,
        'term_result_ids' => $availableResults->pluck('id')->all()
      ]
    );
  }

  private function availableMessage(
    Collection $termResults,
    ?string $senderName
  ): string {
    $firstResult = $termResults->first();
    $student = $firstResult->student;
    $lines = [
      'Your result is ready.',
      '',
      'Student: ' . ($student?->user?->full_name ?? 'Student'),
      'Class: ' . ($firstResult->classification?->title ?? 'N/A'),
      'Term: ' . $this->termLabel($firstResult->term),
      'Session: ' . ($firstResult->academicSession?->title ?? 'N/A'),
      'School: ' . ($firstResult->institution?->name ?? 'N/A'),
      ''
    ];

    foreach ($termResults as $termResult) {
      $lines[] = $this->resultTypeLabel($termResult) . ':';
      $lines[] = $termResult->signedUrl();
      $lines[] = '';
    }

    return $this->withGreeting(rtrim(implode("\n", $lines)), $senderName);
  }

  private function resultLabel(TermResult $termResult): string
  {
    return implode(' - ', [
      $this->termLabel($termResult->term),
      ($termResult->academicSession?->title ?? 'N/A') . ' session',
      $this->resultTypeLabel($termResult)
    ]);
  }

  private function resultTypeLabel(TermResult $termResult): string
  {
    return $termResult->for_mid_term ? 'Mid-term result' : 'Full-term result';
  }

  private function termLabel($term): string
  {
    $value = $term?->value ?? $term;

    return ucfirst(str_replace('-', ' ', (string) $value)) . ' term';
  }

  private function withGreeting(string $message, ?string $senderName): string
  {
    $name = trim((string) $senderName);
    if ($name === '') {
      return $message;
    }

    return "Hi {$name}, " . $message;
  }
}
