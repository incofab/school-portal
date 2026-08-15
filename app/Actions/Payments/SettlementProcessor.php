<?php

namespace App\Actions\Payments;

use App\DTO\Payments\SettlementFailureDto;
use App\DTO\Payments\SettlementInstitutionResultDto;
use App\DTO\Payments\SettlementSummaryDto;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\SettlementStatus;
use App\Models\BankAccount;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SettlementProcessor
{
  public static function make(): self
  {
    return new self();
  }

  public function process(): SettlementSummaryDto
  {
    $summary = new SettlementSummaryDto();

    $this->eligiblePaymentReferences()
      ->select('payment_references.institution_id')
      ->distinct()
      ->orderBy('payment_references.institution_id')
      ->chunk(100, function ($rows) use (&$summary) {
        foreach ($rows as $row) {
          $summary->recordCheckedInstitution();

          try {
            $result = $this->processInstitution((int) $row->institution_id);
          } catch (Throwable $th) {
            $summary->recordFailure(
              new SettlementFailureDto(
                institutionId: (int) $row->institution_id,
                message: $th->getMessage()
              )
            );

            Log::error('Institution settlement failed', [
              'institution_id' => $row->institution_id,
              'exception' => $th
            ]);

            continue;
          }

          $summary->recordResult($result);
        }
      });

    return $summary;
  }

  private function processInstitution(
    int $institutionId
  ): SettlementInstitutionResultDto {
    return DB::transaction(function () use ($institutionId) {
      $institution = Institution::query()
        ->whereKey($institutionId)
        ->with(['institutionGroup.bankAccounts', 'createdBy'])
        ->lockForUpdate()
        ->firstOrFail();

      $paymentReferences = $this->eligiblePaymentReferences()
        ->where('payment_references.institution_id', $institution->id)
        ->lockForUpdate()
        ->get();

      if ($paymentReferences->isEmpty()) {
        return new SettlementInstitutionResultDto(settled: false);
      }

      $amount = round((float) $paymentReferences->sum('amount'), 2);
      $bankAccount = $this->bankAccount($institution);
      $processedAt = now();

      $settlement = Settlement::query()->create([
        'institution_id' => $institution->id,
        'amount' => $amount,
        'status' => SettlementStatus::Completed,
        'processed_at' => $processedAt
      ]);

      $settlement->paymentReferences()->attach(
        $paymentReferences
          ->mapWithKeys(
            fn(PaymentReference $paymentReference) => [
              $paymentReference->id => ['amount' => $paymentReference->amount]
            ]
          )
          ->all()
      );

      PaymentReference::query()
        ->whereKey($paymentReferences->pluck('id'))
        ->update(['settled_at' => $processedAt]);

      $reference = $this->withdrawalReference($settlement);

      $res = WithdrawalHandler::make()->recordInstitutionWithdrawal(
        $institution->institutionGroup,
        $bankAccount,
        $institution->createdBy,
        $amount,
        $reference,
        $institution
      );

      if ($res->isNotSuccessful()) {
        throw new \RuntimeException($res->getMessage());
      }

      $withdrawal = $institution->institutionGroup
        ->withdrawals()
        ->where('reference', $reference)
        ->firstOrFail();

      $settlement->forceFill(['withdrawal_id' => $withdrawal->id])->save();

      return new SettlementInstitutionResultDto(settled: true, amount: $amount);
    });
  }

  private function eligiblePaymentReferences()
  {
    return PaymentReference::query()
      ->where('payment_references.status', PaymentStatus::Confirmed)
      ->whereIn(
        'payment_references.merchant',
        $this->enumValues(PaymentMerchantType::settleable())
      )
      ->whereIn(
        'payment_references.purpose',
        $this->enumValues(PaymentPurpose::settleable())
      )
      ->whereNull('payment_references.settled_at')
      // ->whereDoesntHave('settlement')
      ->orderBy('payment_references.institution_id')
      ->orderBy('payment_references.id');
  }

  private function bankAccount(Institution $institution): BankAccount
  {
    $bankAccount = $institution->institutionGroup?->bankAccounts()->first();

    if (!$bankAccount) {
      throw new \RuntimeException(
        'Institution group has no payout bank account.'
      );
    }

    return $bankAccount;
  }

  private function withdrawalReference(Settlement $settlement): string
  {
    return Str::of('settlement')
      ->append('-', $settlement->institution_id)
      ->append('-', $settlement->id)
      ->toString();
  }

  private function enumValues(array $enums): array
  {
    return array_map(fn($enum) => $enum->value, $enums);
  }
}
