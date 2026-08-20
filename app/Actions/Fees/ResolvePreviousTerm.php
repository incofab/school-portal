<?php

namespace App\Actions\Fees;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Support\SettingsHandler;

class ResolvePreviousTerm
{
  private const TERM_ORDER = [TermType::First, TermType::Second, TermType::Third];

  public function __construct(private Institution $institution)
  {
  }

  /**
   * @return array{term: ?TermType, academic_session_id: ?int}
   */
  public static function run(Institution $institution): array
  {
    return (new self($institution))->execute();
  }

  private function execute(): array
  {
    $settingsHandler = SettingsHandler::makeFromInstitution($this->institution);
    $currentTerm = TermType::tryFrom($settingsHandler->getCurrentTerm());
    $currentSessionId = $settingsHandler->getCurrentAcademicSession();

    if (!$currentTerm) {
      return ['term' => null, 'academic_session_id' => null];
    }

    $index = array_search($currentTerm, self::TERM_ORDER, true);

    if ($index > 0) {
      return [
        'term' => self::TERM_ORDER[$index - 1],
        'academic_session_id' => $currentSessionId
      ];
    }

    return [
      'term' => TermType::Third,
      'academic_session_id' => $this->previousAcademicSessionId($currentSessionId)
    ];
  }

  private function previousAcademicSessionId(?int $currentSessionId): ?int
  {
    $currentSession = $currentSessionId
      ? AcademicSession::find($currentSessionId)
      : null;

    if (!$currentSession) {
      return null;
    }

    return AcademicSession::query()
      ->where(
        fn($q) => $q
          ->where('order_index', '<', $currentSession->order_index)
          ->orWhere(
            fn($qq) => $qq
              ->where('order_index', $currentSession->order_index)
              ->where('id', '<', $currentSession->id)
          )
      )
      ->orderByDesc('order_index')
      ->orderByDesc('id')
      ->first()?->id;
  }
}
