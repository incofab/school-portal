<?php

namespace App\Support;

use App\Core\MonnifyHelper;
use App\Models\BankAccount;
use App\Models\InstitutionGroup;
use App\Models\InstitutionUser;
use App\Models\Partner;
use App\Support\Audit\FinancialActivityLogger;
use Illuminate\Database\Eloquent\Model;

class BankAccountHandler
{
  public function __construct(
    private Model|InstitutionGroup|InstitutionUser|Partner $accountable
  ) {
  }

  public static function make(Model $accountable)
  {
    return new self($accountable);
  }

  public function update(BankAccount $bankAccount, array $post)
  {
    $oldValues = $this->safeSnapshot($bankAccount);

    abort_if(
      $bankAccount->withdrawals()->exists(),
      403,
      'This account has made withdrawals. Cannot be editted'
    );

    if ($post['is_primary'] ?? false) {
      $this->accountable
        ->bankAccounts()
        ->where('is_primary', true)
        ->where('id', '!=', $bankAccount->id)
        ->update(['is_primary' => false]);
    }
    $bankAccount->update($post);

    app(FinancialActivityLogger::class)->bankAccountChanged(
      $bankAccount->refresh(),
      'updated',
      $this->accountable,
      $oldValues
    );
  }

  /**
   * @param array {
   *  bank_code: string,
   *  account_number: string,
   *  is_primary?: bool,
   * }
   */
  public function store(array $post)
  {
    $res = MonnifyHelper::make()->validateBankAccount(
      $post['bank_code'],
      $post['account_number']
    );
    abort_unless($res->isSuccessful(), 400, $res->getMessage());
    if ($post['is_primary'] ?? false) {
      $this->accountable
        ->bankAccounts()
        ->where('is_primary', true)
        ->update(['is_primary' => false]);
    }
    $bankAccount = BankAccount::create([
      ...$post,
      'accountable_type' => $this->accountable->getMorphClass(),
      'accountable_id' => $this->accountable->id,
      'account_name' => $res->account_name
    ]);

    app(FinancialActivityLogger::class)->bankAccountChanged(
      $bankAccount,
      'created',
      $this->accountable
    );
  }

  public function destroy(BankAccount $bankAccount)
  {
    $oldValues = $this->safeSnapshot($bankAccount);

    abort_if(
      $bankAccount->validWithdrawals()->exists(),
      403,
      'This account has made withdrawals. Cannot be deleted'
    );
    abort_unless(
      $this->accountable
        ->bankAccounts()
        ->where('id', $bankAccount->id)
        ->exists(),
      403,
      'Access denied'
    );
    $bankAccount->delete();

    app(FinancialActivityLogger::class)->bankAccountChanged(
      $bankAccount,
      'deleted',
      $this->accountable,
      $oldValues
    );
  }

  private function safeSnapshot(BankAccount $bankAccount): array
  {
    return [
      'id' => $bankAccount->id,
      'bank_name' => $bankAccount->bank_name,
      'bank_code' => $bankAccount->bank_code,
      'account_name' => $bankAccount->account_name,
      'account_number_last4' => $bankAccount->account_number
        ? substr((string) $bankAccount->account_number, -4)
        : null,
      'is_primary' => (bool) $bankAccount->is_primary
    ];
  }
}
