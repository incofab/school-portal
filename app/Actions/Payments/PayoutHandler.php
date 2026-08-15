<?php

namespace App\Actions\Payments;

use App\DTO\Payouts\PayoutBatchRequestDto;
use App\DTO\Payouts\PayoutBeneficiaryDto;
use App\DTO\Payouts\PayoutItemResultDto;
use App\DTO\Payouts\PayoutRequestDto;
use App\DTO\Payments\PayoutProcessingResultDto;
use App\DTO\Payments\PayoutProcessingSummaryDto;
use App\Enums\PayoutPurposeType;
use App\Enums\PayoutStatus;
use App\Enums\WithdrawalStatus;
use App\Models\BankAccount;
use App\Models\Payroll;
use App\Models\Payout;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Payouts\Merchants\PayoutMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayoutHandler
{
  public function __construct(private ?PayoutMerchant $merchant = null)
  {
    $this->merchant ??= PayoutMerchant::make();
  }

  public static function make(?string $merchant = null): self
  {
    return new self(PayoutMerchant::make($merchant));
  }

  public function processWithdrawal(
    Withdrawal $withdrawal,
    ?User $user
  ): PayoutProcessingSummaryDto {
    $summary = new PayoutProcessingSummaryDto();
    $summary->record(
      $this->processSingle($this->withdrawalRequest($withdrawal), $user)
    );

    return $summary;
  }

  public function processPendingWithdrawals(
    ?User $user
  ): PayoutProcessingSummaryDto {
    $withdrawals = Withdrawal::query()
      ->with(['bankAccount', 'payout'])
      ->where('status', WithdrawalStatus::Pending)
      ->whereNull('paid_at')
      ->whereDoesntHave(
        'payout',
        fn($query) => $query->where('is_processing', true)
      )
      ->orderBy('id')
      ->get();

    if ($withdrawals->count() <= 1) {
      $summary = new PayoutProcessingSummaryDto();
      foreach ($withdrawals as $withdrawal) {
        $summary->record(
          $this->processSingle($this->withdrawalRequest($withdrawal), $user)
        );
      }

      return $summary;
    }

    return $this->processBulk(
      new PayoutBatchRequestDto(
        batchReference: $this->batchReference('withdrawals'),
        title: 'EduManager withdrawals',
        narration: 'EduManager withdrawal payouts',
        items: $withdrawals
          ->map(
            fn(Withdrawal $withdrawal) => $this->withdrawalRequest($withdrawal)
          )
          ->all()
      ),
      $user
    );
  }

  /**
   * Entry point for paying already generated payroll records.
   *
   * @param array<int, array{payroll: Payroll, bankAccount: BankAccount}> $items
   */
  public function processPayrollBulk(
    array $items,
    ?User $user
  ): PayoutProcessingSummaryDto {
    return $this->processBulk(
      new PayoutBatchRequestDto(
        batchReference: $this->batchReference('payroll'),
        title: 'EduManager payroll',
        narration: 'EduManager payroll payouts',
        items: array_map(
          fn(array $item) => $this->payrollRequest(
            $item['payroll'],
            $item['bankAccount']
          ),
          $items
        )
      ),
      $user
    );
  }

  public function refreshPendingStatuses(
    ?User $user = null,
    int $limit = 20
  ): PayoutProcessingSummaryDto {
    $summary = new PayoutProcessingSummaryDto();

    Payout::query()
      ->where('merchant', $this->merchant->merchant()->value)
      ->whereIn('status', [
        PayoutStatus::Pending->value,
        PayoutStatus::Unknown->value
      ])
      ->where('is_processing', false)
      ->orderBy('attempted_at')
      ->limit($limit)
      ->get()
      ->each(function (Payout $payout) use ($summary, $user) {
        $summary->record(
          $this->applyResult(
            $payout,
            $this->merchant->verify($payout->reference),
            $user
          )
        );
      });

    return $summary;
  }

  private function processSingle(
    PayoutRequestDto $request,
    ?User $user
  ): PayoutProcessingResultDto {
    $prepared = $this->preparePayout($request);
    $payout = $prepared->payout;

    if (!$payout) {
      return $this->skipped(
        $request->payoutable,
        'Payoutable is no longer eligible.'
      );
    }

    if ($prepared->alreadyProcessing) {
      return $this->result(
        $payout,
        'skipped',
        'PROCESSING',
        'A payout API call is already in progress.'
      );
    }

    if ($prepared->hadExistingAttempt) {
      return $this->applyResult(
        $payout,
        $this->merchant->verify($payout->reference),
        $user
      );
    }

    $validation = $this->validateRequest($request);
    if ($validation) {
      $this->markLocalFailure($payout, 'VALIDATION_FAILED', $validation);

      return $this->result($payout, 'failed', 'VALIDATION_FAILED', $validation);
    }

    $this->markAttempting($payout);

    return $this->applyResult(
      $payout,
      $this->merchant->initiateSingle($request),
      $user
    );
  }

  private function processBulk(
    PayoutBatchRequestDto $batch,
    ?User $user
  ): PayoutProcessingSummaryDto {
    $summary = new PayoutProcessingSummaryDto();
    $prepared = [];

    foreach ($batch->items as $request) {
      $preparation = $this->preparePayout($request, $batch->batchReference);
      if (!$preparation->payout) {
        $summary->record(
          $this->skipped(
            $request->payoutable,
            'Payoutable is no longer eligible.'
          )
        );
        continue;
      }
      if ($preparation->alreadyProcessing) {
        $summary->record(
          $this->result(
            $preparation->payout,
            'skipped',
            'PROCESSING',
            'A payout API call is already in progress.'
          )
        );
        continue;
      }
      if ($preparation->hadExistingAttempt) {
        $summary->record(
          $this->applyResult(
            $preparation->payout,
            $this->merchant->verify($preparation->payout->reference),
            $user
          )
        );
        continue;
      }

      $validation = $this->validateRequest($request);
      if ($validation) {
        $this->markLocalFailure(
          $preparation->payout,
          'VALIDATION_FAILED',
          $validation
        );
        $summary->record(
          $this->result(
            $preparation->payout,
            'failed',
            'VALIDATION_FAILED',
            $validation
          )
        );
        continue;
      }

      $this->markAttempting($preparation->payout, $batch->batchReference);
      $prepared[$request->reference] = [$request, $preparation->payout];
    }

    if ($prepared === []) {
      return $summary;
    }

    $bulkResult = $this->merchant->initiateBulk(
      new PayoutBatchRequestDto(
        batchReference: $batch->batchReference,
        title: $batch->title,
        narration: $batch->narration,
        items: array_map(fn(array $item) => $item[0], $prepared)
      )
    );

    foreach ($bulkResult->items as $itemResult) {
      if (!isset($prepared[$itemResult->reference])) {
        continue;
      }

      $summary->record(
        $this->applyResult(
          $prepared[$itemResult->reference][1],
          $itemResult,
          $user
        )
      );
      unset($prepared[$itemResult->reference]);
    }

    foreach ($prepared as [, $payout]) {
      $unknown = new PayoutItemResultDto(
        reference: $payout->reference,
        status: PayoutStatus::Unknown,
        merchantStatus: $bulkResult->merchantStatus,
        message: $bulkResult->message,
        providerResponse: $bulkResult->providerResponse
      );
      $summary->record($this->applyResult($payout, $unknown, $user));
    }

    return $summary;
  }

  private function preparePayout(
    PayoutRequestDto $request,
    ?string $batchReference = null
  ): \App\DTO\Payments\PayoutPreparationDto {
    return DB::transaction(function () use ($request, $batchReference) {
      $payoutable = $request->payoutable
        ->newQuery()
        ->whereKey($request->payoutable->getKey())
        ->lockForUpdate()
        ->first();

      if (!$payoutable || !$this->isEligible($payoutable)) {
        return new \App\DTO\Payments\PayoutPreparationDto($payoutable, null);
      }

      $payout =
        $payoutable
          ->payout()
          ->lockForUpdate()
          ->first() ?? $this->createPayout($request, $batchReference);

      if ($payout->is_processing) {
        return new \App\DTO\Payments\PayoutPreparationDto(
          $payoutable,
          $payout,
          alreadyProcessing: true
        );
      }

      $hadExistingAttempt =
        $payout->attempted_at !== null && $payout->attempt_count > 0;

      $payout
        ->forceFill([
          'batch_reference' => $batchReference ?? $payout->batch_reference,
          'is_processing' => true,
          'status' => $payout->status ?? PayoutStatus::Initiated,
          'merchant_status' => $payout->merchant_status ?? 'INITIATED'
        ])
        ->save();

      return new \App\DTO\Payments\PayoutPreparationDto(
        $payoutable,
        $payout,
        hadExistingAttempt: $hadExistingAttempt
      );
    });
  }

  private function createPayout(
    PayoutRequestDto $request,
    ?string $batchReference
  ): Payout {
    return $request->payoutable->payout()->create([
      'purpose' => $request->purpose,
      'merchant' => $this->merchant->merchant(),
      'status' => PayoutStatus::Initiated,
      'merchant_status' => 'INITIATED',
      'reference' => $request->reference,
      'batch_reference' => $batchReference,
      'amount' => $request->amount,
      'currency' => $request->currency,
      'is_processing' => false,
      'attempt_count' => 0,
      'note' => 'Payout record created.'
    ]);
  }

  private function applyResult(
    Payout $payout,
    PayoutItemResultDto $item,
    ?User $user
  ): PayoutProcessingResultDto {
    $status = $item->status;
    $group = match ($status) {
      PayoutStatus::Successful => 'successful',
      PayoutStatus::Failed => 'failed',
      default => 'pending'
    };

    DB::transaction(function () use ($payout, $item, $status, $user) {
      $locked = Payout::query()
        ->whereKey($payout->id)
        ->lockForUpdate()
        ->firstOrFail();

      if ($locked->status === PayoutStatus::Successful) {
        return;
      }

      $locked
        ->forceFill([
          'status' => $status,
          'merchant_status' => $item->merchantStatus,
          'provider_reference' =>
            $item->providerReference ?? $locked->provider_reference,
          'provider_response' => $this->scrubProviderResponse(
            $item->providerResponse
          ),
          'is_processing' => false,
          'completed_at' => in_array(
            $status,
            [PayoutStatus::Successful, PayoutStatus::Failed],
            true
          )
            ? $locked->completed_at ?? now()
            : $locked->completed_at,
          'note' => $item->message
        ])
        ->save();

      $this->syncPayoutable($locked, $status, $user, $item->message);
    });

    return $this->result(
      $payout->refresh(),
      $group,
      $item->merchantStatus,
      $item->message
    );
  }

  private function syncPayoutable(
    Payout $payout,
    PayoutStatus $status,
    ?User $user,
    string $message
  ): void {
    $payout->loadMissing('payoutable');

    if (!$payout->payoutable instanceof Withdrawal) {
      return;
    }

    if ($status === PayoutStatus::Successful) {
      $payout->payoutable
        ->forceFill([
          'processed_by_user_id' => $user?->id,
          'status' => WithdrawalStatus::Paid,
          'paid_at' => $payout->payoutable->paid_at ?? now()
        ])
        ->save();
    }

    if ($status === PayoutStatus::Failed) {
      WithdrawalHandler::make()->declineWithdrawal(
        $payout->payoutable,
        $user,
        $message
      );
    }
  }

  private function markAttempting(
    Payout $payout,
    ?string $batchReference = null
  ): void {
    $payout
      ->forceFill([
        'attempt_count' => $payout->attempt_count + 1,
        'attempted_at' => now(),
        'batch_reference' => $batchReference ?? $payout->batch_reference,
        'status' => PayoutStatus::Initiated,
        'merchant_status' => 'INITIATED',
        'is_processing' => true,
        'note' => 'Payout request sent.'
      ])
      ->save();
  }

  private function markLocalFailure(
    Payout $payout,
    string $status,
    string $note
  ): void {
    $payout
      ->forceFill([
        'status' => PayoutStatus::Failed,
        'merchant_status' => $status,
        'is_processing' => false,
        'completed_at' => now(),
        'note' => $note
      ])
      ->save();
  }

  private function withdrawalRequest(Withdrawal $withdrawal): PayoutRequestDto
  {
    $withdrawal->loadMissing('bankAccount');

    return new PayoutRequestDto(
      payoutable: $withdrawal,
      purpose: PayoutPurposeType::Withdrawal,
      amount: $withdrawal->amount,
      beneficiary: $this->beneficiary($withdrawal->bankAccount),
      narration: "EduManager withdrawal {$withdrawal->id}",
      reference: $this->reference('withdrawal', $withdrawal)
    );
  }

  private function payrollRequest(
    Payroll $payroll,
    BankAccount $bankAccount
  ): PayoutRequestDto {
    return new PayoutRequestDto(
      payoutable: $payroll,
      purpose: PayoutPurposeType::Payroll,
      amount: $payroll->net_salary,
      beneficiary: $this->beneficiary($bankAccount),
      narration: "EduManager payroll {$payroll->id}",
      reference: $this->reference('payroll', $payroll)
    );
  }

  private function beneficiary(?BankAccount $bankAccount): PayoutBeneficiaryDto
  {
    return new PayoutBeneficiaryDto(
      accountNumber: (string) $bankAccount?->account_number,
      bankCode: (string) $bankAccount?->bank_code,
      accountName: (string) $bankAccount?->account_name,
      bankName: $bankAccount?->bank_name
    );
  }

  private function validateRequest(PayoutRequestDto $request): ?string
  {
    if ($request->amount <= 0) {
      return 'Payout amount must be greater than zero.';
    }

    if (!$request->beneficiary->bankCode) {
      return 'Payout bank code is missing.';
    }

    if (!$request->beneficiary->accountNumber) {
      return 'Payout account number is missing.';
    }

    if (!$request->beneficiary->accountName) {
      return 'Payout account name is missing.';
    }

    return null;
  }

  private function isEligible(Model $payoutable): bool
  {
    if ($payoutable instanceof Withdrawal) {
      return $payoutable->status === WithdrawalStatus::Pending &&
        $payoutable->paid_at === null;
    }

    return true;
  }

  private function reference(string $prefix, Model $model): string
  {
    return Str::of('edumanager')
      ->append('-', $prefix)
      ->append('-', $model->getKey())
      ->lower()
      ->toString();
  }

  private function batchReference(string $prefix): string
  {
    return Str::of('edumanager')
      ->append('-', $this->merchant->merchant()->value)
      ->append('-', $prefix)
      ->append('-batch-', Str::uuid()->toString())
      ->lower()
      ->toString();
  }

  private function result(
    Payout $payout,
    string $group,
    string $status,
    string $note
  ): PayoutProcessingResultDto {
    return PayoutProcessingResultDto::fromPayout(
      $payout,
      $group,
      $status,
      $note
    );
  }

  private function skipped(
    Model $model,
    string $note
  ): PayoutProcessingResultDto {
    return new PayoutProcessingResultDto(
      group: 'skipped',
      payoutableId: (int) $model->getKey(),
      payoutableType: $model->getMorphClass(),
      status: 'SKIPPED',
      note: $note
    );
  }

  private function scrubProviderResponse(array $response): array
  {
    unset(
      $response['authorization'],
      $response['Authorization'],
      $response['secret'],
      $response['token']
    );

    return $response;
  }
}
