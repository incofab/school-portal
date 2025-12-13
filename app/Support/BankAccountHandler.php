<?php
namespace App\Support;

use App\Core\MonnifyHelper;
use App\Models\BankAccount;
use App\Models\InstitutionGroup;
use App\Models\InstitutionUser;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Model;

class BankAccountHandler
{
  function __construct(
    private Model|InstitutionGroup|InstitutionUser|Partner $accountable
  ) {
  }

  static function make(Model $accountable)
  {
    return new self($accountable);
  }

  public function update(BankAccount $bankAccount, array $post)
  {
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
  }

  /**
   * @param array {
   *  bank_code: string,
   *  account_number: string,
   *  is_primary?: bool,
   * }
   */
  function store(array $post)
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
    BankAccount::create([
      ...$post,
      'accountable_type' => $this->accountable->getMorphClass(),
      'accountable_id' => $this->accountable->id,
      'account_name' => $res->account_name
    ]);
  }

  function destroy(BankAccount $bankAccount)
  {
    abort_if(
      $bankAccount->withdrawals()->exists(),
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
  }
}
