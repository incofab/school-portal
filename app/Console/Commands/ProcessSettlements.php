<?php

namespace App\Console\Commands;

use App\Actions\Payments\SettlementProcessor;
use Illuminate\Console\Command;

class ProcessSettlements extends Command
{
  protected $signature = 'settlements:process';

  protected $description = 'Process unsettled institution payments';

  public function handle(): int
  {
    $summary = SettlementProcessor::make()->process();

    $message = sprintf(
      'Settlement completed: %d institutions checked, %d settled, %d skipped, ₦%s total withdrawals created, %d failed.',
      $summary->institutionsChecked,
      $summary->settled,
      $summary->skipped,
      number_format($summary->totalAmount, 2),
      $summary->failed
    );

    $this->info($message);
    info($message, [
      'failures' => array_map(
        fn($failure) => $failure->toArray(),
        $summary->failures
      )
    ]);

    return self::SUCCESS;
  }
}
