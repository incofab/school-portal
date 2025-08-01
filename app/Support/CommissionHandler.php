<?php
namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Commission;
use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Model;

class CommissionHandler
{
  function __construct(private string $reference)
  {
  }

  static function make(string $reference)
  {
    return new self($reference);
  }

  //= Credit multiple Partners :: Partner and Partner's Referral
  function creditPartners(
    InstitutionGroup $institutionGroup,
    float $amountSpent,
    ?Model $commissionable
  ) {
    $partner = $institutionGroup->partner?->partner;

    if (!$partner) {
      return;
    }

    $commission = $amountSpent * ($partner->commission / 100);

    $transactionable = Commission::create([
      'institution_group_id' => $institutionGroup->id,
      'partner_id' => $partner->id,
      'commissionable_id' => $commissionable?->id,
      'commissionable_type' => $commissionable?->getMorphClass(),
      'amount' => $commission
    ]);
    $this->topupWallet($commission, $partner, $transactionable);

    $refPartner = $partner->referral;
    if (!$refPartner) {
      return;
    }

    $refCommission = $amountSpent * ($partner->referral_commission / 100);

    $transactionable = Commission::create([
      'institution_group_id' => $institutionGroup->id,
      'partner_id' => $refPartner->id,
      'commissionable_id' => $commissionable?->id,
      'commissionable_type' => $commissionable?->getMorphClass(),
      'amount' => $refCommission
    ]);

    $this->topupWallet($refCommission, $refPartner, $transactionable);
  }

  //= Debit a Partner's Wallet ::: When he/she request for withdrawal
  function debitPartner(Partner $partner, float $amount, Model $transactionable)
  {
    $this->recordTransaction(
      $amount,
      $partner,
      TransactionType::Debit,
      $transactionable
    );
  }

  //= Refund a Partner's Wallet ::: When his/her withdrawal request is DECLINED
  function refundPartner(
    Partner $partner,
    Withdrawal $withdrawal,
    ?string $remark = null
  ) {
    $this->topupWallet($withdrawal->amount, $partner, $withdrawal, $remark);
  }

  function topupWallet(
    $amount,
    Partner $partner,
    Model $transactionable,
    $remark = ''
  ) {
    $this->recordTransaction(
      $amount,
      $partner,
      TransactionType::Credit,
      $transactionable,
      $remark
    );
  }

  private function recordTransaction(
    $amount,
    Partner $partner,
    TransactionType $transactionType,
    Model $transactionable,
    $remark = ''
  ) {
    UserTransactionHandler::recordTransaction(
      amount: $amount,
      entity: $partner,
      transactionType: $transactionType,
      transactionable: $transactionable,
      reference: $this->reference,
      remark: $remark
    );
    // $bbt = $partner->wallet;
    // $bat =
    //   $transactionType === TransactionType::Credit
    //     ? $bbt + $amount
    //     : $bbt - $amount;
    // $partner->fill(['wallet' => $bat])->save();

    // //= Save to UserTransactions DB Table
    // UserTransaction::Create([
    //   'type' => $transactionType,
    //   'amount' => $amount,
    //   'bbt' => $bbt,
    //   'bat' => $bat,
    //   'entity_type' => $partner->getMorphClass(),
    //   'entity_id' => $partner->id,
    //   'transactionable_type' => $transactionable?->getMorphClass(),
    //   'transactionable_id' => $transactionable?->id,
    //   'reference' => $this->reference,
    //   'remark' => $remark
    // ]);
  }
}
