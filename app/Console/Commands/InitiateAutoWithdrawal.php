<?php

namespace App\Console\Commands;

use App\Actions\Payments\WithdrawalHandler;
use App\Models\Institution;
use App\Models\PaymentReference;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Str;

class InitiateAutoWithdrawal extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:auto-withdrawal';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Automatically initiate withdrawal for unbehalf of the institutions';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $institutions = Institution::query()
      ->with('institutionGroup.bankAccounts')
      ->get();
    foreach ($institutions as $key => $institution) {
      $paymentReferences = $institution
        ->paymentReferences()
        ->getQuery()
        ->confirmed()
        ->isProcessed(false)
        ->get();
      $amount = collect()->sum(fn($item) => $item->amount);
      $institutionGroup = $institution->institutionGroup;
      $withdrawableBalance = $institutionGroup->credit_wallet - 50000;

      if ($withdrawableBalance < 1000) {
        $this->markAsProcessed($paymentReferences);
        continue;
      }

      $bankAccount = $institutionGroup->bankAccounts()->first();
      WithdrawalHandler::make()->recordInstitutionWithdrawal(
        $institutionGroup,
        $bankAccount,
        $institution->createdBy,
        $amount,
        Str::orderedUuid(),
        $institution
      );

      $this->markAsProcessed($paymentReferences);
    }
  }

  private function markAsProcessed(Collection $paymentReferences)
  {
    $ids = $paymentReferences->pluck('id')->toArray();
    PaymentReference::query()
      ->whereIn('id', $ids)
      ->update(['processed_at' => now()]);
  }
}
