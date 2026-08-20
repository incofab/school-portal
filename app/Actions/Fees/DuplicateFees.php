<?php

namespace App\Actions\Fees;

use App\Actions\Payments\RecordFee;
use App\Enums\TermType;
use App\Models\Fee;
use App\Models\Institution;
use App\Support\SettingsHandler;
use Illuminate\Support\Collection;

class DuplicateFees
{
  public function __construct(
    private Institution $institution,
    private array $feeIds
  ) {
  }

  /**
   * @return array{created: Collection<int, Fee>, skipped: Collection<int, Fee>}
   */
  public static function run(Institution $institution, array $feeIds): array
  {
    return (new self($institution, $feeIds))->execute();
  }

  private function execute(): array
  {
    $created = collect();
    $skipped = collect();

    if (empty($this->feeIds)) {
      return compact('created', 'skipped');
    }

    ['term' => $previousTerm, 'academic_session_id' => $previousSessionId] =
      ResolvePreviousTerm::run($this->institution);

    if (!$previousTerm || !$previousSessionId) {
      return compact('created', 'skipped');
    }

    $settingsHandler = SettingsHandler::makeFromInstitution($this->institution);
    $targetTerm = TermType::tryFrom($settingsHandler->getCurrentTerm());
    $targetSessionId = $settingsHandler->getCurrentAcademicSession();

    if (!$targetTerm || !$targetSessionId) {
      return compact('created', 'skipped');
    }

    // Only fees genuinely belonging to the resolved previous term/session
    // may be duplicated, regardless of what ids were submitted.
    $sourceFees = Fee::query()
      ->where('term', $previousTerm->value)
      ->where('academic_session_id', $previousSessionId)
      ->whereIn('id', $this->feeIds)
      ->with('feeCategories')
      ->get();

    foreach ($sourceFees as $sourceFee) {
      // A fee sharing the same title in the target term/session is treated
      // as an already-duplicated copy of this one.
      $alreadyDuplicated = Fee::query()
        ->where('title', $sourceFee->title)
        ->where('academic_session_id', $targetSessionId)
        ->where('term', $targetTerm->value)
        ->exists();

      if ($alreadyDuplicated) {
        $skipped->push($sourceFee);
        continue;
      }

      $newFee = RecordFee::run(
        [
          'title' => $sourceFee->title,
          'amount' => $sourceFee->amount,
          'payment_interval' => $sourceFee->payment_interval?->value,
          'term' => $targetTerm->value,
          'academic_session_id' => $targetSessionId,
          'fee_items' => $sourceFee->fee_items?->getArrayCopy() ?? [],
          'fee_categories' => $sourceFee->feeCategories
            ->map(
              fn($category) => [
                'feeable_id' => $category->feeable_id,
                'feeable_type' => $category->feeable_type
              ]
            )
            ->all()
        ],
        $this->institution
      );

      $created->push($newFee);
    }

    return compact('created', 'skipped');
  }
}
