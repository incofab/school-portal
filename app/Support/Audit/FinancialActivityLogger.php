<?php

namespace App\Support\Audit;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use App\Models\BankAccount;
use App\Models\Commission;
use App\Models\Expense;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\ManualPayment;
use App\Models\Salary;
use App\Models\SchoolNotification;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Model;

class FinancialActivityLogger
{
  public function feeRecorded(
    Fee $fee,
    string $action,
    array $oldValues = []
  ): void {
    $fee->loadMissing(
      'institution.institutionGroup',
      'academicSession',
      'feeCategories.feeable'
    );

    $logger = $this->base(
      "finance.fee.$action",
      ActivityLogCategory::Fee,
      $action
    )
      ->on($fee)
      ->inInstitution($fee->institution)
      ->description("Fee {$action}.")
      ->properties([
        ...$this->money($fee->amount),
        'fee_id' => $fee->id,
        'title' => $fee->title,
        'academic_session_id' => $fee->academic_session_id,
        'academic_session' => $fee->academicSession?->title,
        'term' => $this->enumValue($fee->term),
        'payment_interval' => $this->enumValue($fee->payment_interval),
        'assignments' => $this->feeAssignments($fee)
      ])
      ->severity(
        $action === 'deleted'
          ? ActivityLogSeverity::Critical
          : ActivityLogSeverity::Notice
      );

    if ($oldValues !== []) {
      $logger->oldValues($oldValues);
    }

    $logger->newValues($this->feeSnapshot($fee))->log();
  }

  public function manualPaymentReviewed(
    ManualPayment $manualPayment,
    User $actor,
    bool $approved
  ): void {
    $manualPayment->loadMissing(
      'institution.institutionGroup',
      'user',
      'bankAccount',
      'payable',
      'paymentable'
    );

    $action = $approved ? 'approved' : 'rejected';

    $this->base(
      "finance.manual_payment.$action",
      ActivityLogCategory::Payment,
      $action
    )
      ->by($actor)
      ->on($manualPayment)
      ->inInstitution($manualPayment->institution)
      ->severity(
        $approved
          ? ActivityLogSeverity::Critical
          : ActivityLogSeverity::Security
      )
      ->description("Manual payment {$action}.")
      ->properties([
        ...$this->paymentRecord($manualPayment),
        'status' => $this->enumValue($manualPayment->status),
        'approval_actor' => $this->userSnapshot($actor),
        'review_note_present' => filled($manualPayment->review_note)
      ])
      ->newValues([
        'status' => $this->enumValue($manualPayment->status),
        'reviewed_at' => $manualPayment->reviewed_at,
        'processed_at' => $manualPayment->processed_at
      ])
      ->log();
  }

  public function paymentNotificationSent(
    SchoolNotification $notification,
    Fee $fee,
    string $channel,
    int $receiverCount
  ): void {
    $fee->loadMissing('institution');

    $this->base(
      'finance.payment_notification.sent',
      ActivityLogCategory::Notification,
      'sent'
    )
      ->on($notification)
      ->inInstitution($fee->institution)
      ->description('Payment notification sent.')
      ->properties([
        'notification_id' => $notification->id,
        'reference' => $notification->reference,
        'channel' => $channel,
        'receiver_count' => $receiverCount,
        'fee' => $this->feeSnapshot($fee)
      ])
      ->severity(ActivityLogSeverity::Notice)
      ->log();
  }

  public function paymentCredentialsChanged(
    Institution $institution,
    array $oldValue,
    array $newValue
  ): void {
    $this->base(
      'finance.payment_credentials.updated',
      ActivityLogCategory::Integration,
      'updated'
    )
      ->inInstitution($institution)
      ->description('External payment credentials updated.')
      ->properties([
        'institution_id' => $institution->id,
        'providers' => array_values(
          array_unique([...array_keys($oldValue), ...array_keys($newValue)])
        )
      ])
      ->oldValues(['credentials' => $this->credentialSummary($oldValue)])
      ->newValues(['credentials' => $this->credentialSummary($newValue)])
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function withdrawalProcessed(
    Withdrawal $withdrawal,
    User $actor,
    string $status
  ): void {
    $withdrawal->loadMissing('bankAccount', 'withdrawable');
    [$institution, $institutionGroup] = $this->contextFromModel(
      $withdrawal->withdrawable
    );
    $action = $status === 'paid' ? 'approved' : 'rejected';

    $this->base(
      "finance.withdrawal.$action",
      ActivityLogCategory::Wallet,
      $action
    )
      ->by($actor)
      ->on($withdrawal)
      ->inInstitution($institution)
      ->inInstitutionGroup($institutionGroup)
      ->description("Withdrawal {$action}.")
      ->properties([
        ...$this->money($withdrawal->amount),
        'reference' => $withdrawal->reference,
        'status' => $this->enumValue($withdrawal->status),
        'approval_actor' => $this->userSnapshot($actor),
        'withdrawable' => $this->modelSnapshot($withdrawal->withdrawable),
        'bank_account' => $this->bankAccountSnapshot($withdrawal->bankAccount),
        'remark_present' => filled($withdrawal->remark)
      ])
      ->severity(ActivityLogSeverity::Critical)
      ->newValues([
        'status' => $this->enumValue($withdrawal->status),
        'processed_by_user_id' => $withdrawal->processed_by_user_id,
        'paid_at' => $withdrawal->paid_at
      ])
      ->log();
  }

  public function bankAccountChanged(
    BankAccount $bankAccount,
    string $action,
    ?Model $accountable = null,
    array $oldValues = []
  ): void {
    $bankAccount->loadMissing('accountable');
    $accountable ??= $bankAccount->accountable;
    [$institution, $institutionGroup] = $this->contextFromModel($accountable);

    $logger = $this->base(
      "finance.bank_account.$action",
      ActivityLogCategory::Wallet,
      $action
    )
      ->on($bankAccount)
      ->inInstitution($institution)
      ->inInstitutionGroup($institutionGroup)
      ->description("Bank account {$action}.")
      ->properties([
        'bank_account' => $this->bankAccountSnapshot($bankAccount),
        'accountable' => $this->modelSnapshot($accountable)
      ])
      ->severity(ActivityLogSeverity::Security)
      ->newValues($this->bankAccountSnapshot($bankAccount));

    if ($oldValues !== []) {
      $logger->oldValues($oldValues);
    }

    $logger->log();
  }

  public function commissionUpdated(Commission $commission): void
  {
    $commission->loadMissing(
      'institutionGroup',
      'partner.user',
      'commissionable'
    );

    $this->base(
      'finance.commission.updated',
      ActivityLogCategory::Wallet,
      'updated'
    )
      ->on($commission)
      ->inInstitutionGroup($commission->institutionGroup)
      ->description('Commission updated.')
      ->properties([
        ...$this->money($commission->amount),
        'commission_id' => $commission->id,
        'institution_group' => $this->modelSnapshot(
          $commission->institutionGroup
        ),
        'partner' => $this->modelSnapshot($commission->partner),
        'commissionable' => $this->modelSnapshot($commission->commissionable)
      ])
      ->severity(ActivityLogSeverity::Critical)
      ->log();
  }

  public function payrollItemChanged(
    Salary $salary,
    string $action,
    array $oldValues = []
  ): void {
    $salary->loadMissing(
      'institution.institutionGroup',
      'salaryType',
      'institutionUser.user'
    );

    $logger = $this->base(
      "finance.payroll_item.$action",
      ActivityLogCategory::Payroll,
      $action
    )
      ->on($salary)
      ->inInstitution($salary->institution)
      ->description("Payroll item {$action}.")
      ->properties([
        ...$this->money($salary->amount),
        'salary_id' => $salary->id,
        'salary_type' => $salary->salaryType?->title,
        'payee' => $this->modelSnapshot($salary->institutionUser?->user)
      ])
      ->severity(
        $action === 'deleted'
          ? ActivityLogSeverity::Critical
          : ActivityLogSeverity::Notice
      )
      ->newValues($this->salarySnapshot($salary));

    if ($oldValues !== []) {
      $logger->oldValues($oldValues);
    }

    $logger->log();
  }

  public function expenseDecision(
    Expense $expense,
    string $action,
    array $oldValues = []
  ): void {
    $expense->loadMissing(
      'institution.institutionGroup',
      'expenseCategory',
      'institutionUser.user'
    );

    $logger = $this->base(
      "finance.expense.$action",
      ActivityLogCategory::Expense,
      $action
    )
      ->on($expense)
      ->inInstitution($expense->institution)
      ->description("Expense {$action}.")
      ->properties([
        ...$this->money($expense->amount),
        'expense_id' => $expense->id,
        'title' => $expense->title,
        'category' => $expense->expenseCategory?->title,
        'payee' => $this->modelSnapshot($expense->institutionUser?->user)
      ])
      ->severity(ActivityLogSeverity::Critical)
      ->newValues([
        'action' => $action,
        'amount' => $expense->amount,
        'expense_date' => $expense->expense_date
      ]);

    if ($oldValues !== []) {
      $logger->oldValues($oldValues);
    }

    $logger->log();
  }

  private function base(
    string $event,
    ActivityLogCategory $category,
    string $action
  ): ActivityLogger {
    return app(ActivityLogger::class)
      ->event($event)
      ->category($category)
      ->action($action);
  }

  private function paymentRecord(ManualPayment $payment): array
  {
    return [
      ...$this->money($payment->amount),
      'reference' => $payment->reference,
      'payment_provider' => $this->enumValue($payment->getPaymentMerchant()),
      'payment_method' => $this->enumValue($payment->method),
      'purpose' => $this->enumValue($payment->purpose),
      'payer' => $this->userSnapshot($payment->user),
      'payable' => $this->modelSnapshot($payment->payable),
      'paymentable' => $this->modelSnapshot($payment->paymentable),
      'bank_account' => $this->bankAccountSnapshot($payment->bankAccount)
    ];
  }

  private function feeSnapshot(Fee $fee): array
  {
    return [
      'id' => $fee->id,
      'title' => $fee->title,
      'amount' => $fee->amount,
      'currency' => config('app.currency'),
      'payment_interval' => $this->enumValue($fee->payment_interval),
      'academic_session_id' => $fee->academic_session_id,
      'term' => $this->enumValue($fee->term)
    ];
  }

  private function feeAssignments(Fee $fee): array
  {
    return $fee->feeCategories
      ->map(
        fn($category) => [
          'feeable_type' => $category->feeable_type,
          'feeable_id' => $category->feeable_id,
          'feeable_name' => $this->displayName($category->feeable)
        ]
      )
      ->values()
      ->all();
  }

  private function salarySnapshot(Salary $salary): array
  {
    return [
      'id' => $salary->id,
      'amount' => $salary->amount,
      'currency' => config('app.currency'),
      'salary_type_id' => $salary->salary_type_id,
      'institution_user_id' => $salary->institution_user_id,
      'description' => $salary->description
    ];
  }

  private function bankAccountSnapshot(?BankAccount $bankAccount): ?array
  {
    if (!$bankAccount) {
      return null;
    }

    return [
      'id' => $bankAccount->id,
      'bank_name' => $bankAccount->bank_name,
      'bank_code' => $bankAccount->bank_code,
      'account_name' => $bankAccount->account_name,
      'account_number_last4' => $bankAccount->account_number
        ? substr((string) $bankAccount->account_number, -4)
        : null,
      'is_primary' => (bool) $bankAccount->is_primary,
      'accountable_type' => $bankAccount->accountable_type,
      'accountable_id' => $bankAccount->accountable_id
    ];
  }

  private function credentialSummary(array $credentials): array
  {
    return collect($credentials)
      ->map(
        fn($providerCredentials) => [
          'public_key_present' => filled(
            $providerCredentials['public_key'] ?? null
          ),
          'private_key_present' => filled(
            $providerCredentials['private_key'] ?? null
          ),
          'secret_key_present' => filled(
            $providerCredentials['secret_key'] ?? null
          )
        ]
      )
      ->all();
  }

  private function money(float|int|null $amount): array
  {
    return [
      'amount' => $amount,
      'currency' => config('app.currency')
    ];
  }

  private function userSnapshot(?User $user): ?array
  {
    if (!$user) {
      return null;
    }

    return [
      'id' => $user->id,
      'name' => $user->full_name,
      'email' => $user->email
    ];
  }

  private function modelSnapshot(?Model $model): ?array
  {
    if (!$model) {
      return null;
    }

    return [
      'type' => $model->getMorphClass(),
      'id' => $model->getKey(),
      'name' => $this->displayName($model)
    ];
  }

  private function contextFromModel(?Model $model): array
  {
    if ($model instanceof Institution) {
      return [$model, $model->institutionGroup];
    }

    if ($model instanceof InstitutionGroup) {
      return [null, $model];
    }

    if ($model && method_exists($model, 'institution')) {
      $institution = $model->relationLoaded('institution')
        ? $model->institution
        : $model->institution()->first();

      if ($institution instanceof Institution) {
        return [$institution, $institution->institutionGroup];
      }
    }

    if ($model && method_exists($model, 'institutionGroup')) {
      $institutionGroup = $model->relationLoaded('institutionGroup')
        ? $model->institutionGroup
        : $model->institutionGroup()->first();

      if ($institutionGroup instanceof InstitutionGroup) {
        return [null, $institutionGroup];
      }
    }

    return [currentInstitution(), currentInstitution()?->institutionGroup];
  }

  private function displayName(?Model $model): ?string
  {
    if (!$model) {
      return null;
    }

    foreach (
      ['full_name', 'name', 'title', 'reference', 'email']
      as $attribute
    ) {
      if (filled($model->getAttribute($attribute))) {
        return (string) $model->getAttribute($attribute);
      }
    }

    return class_basename($model) . ' #' . $model->getKey();
  }

  private function enumValue(mixed $value): mixed
  {
    return $value instanceof \BackedEnum ? $value->value : $value;
  }
}
