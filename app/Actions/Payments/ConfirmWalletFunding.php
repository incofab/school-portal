<?php

namespace App\Actions\Payments;

use DB;
use App\Models\Funding;
use App\Models\Institution;
use App\Core\PaystackHelper;
use App\Models\PaymentReference;
use App\Models\Transaction;

/** @deprecated */
class ConfirmWalletFunding
{
  public function __construct(
    private PaymentReference $paymentReference,
    private Institution $institution
  ) {
  }

  function run()
  {
    $res = PaystackHelper::make()->verifyReference(
      $this->paymentReference->reference,
      $this->paymentReference->purpose->value
    );

    if ($res->isNotSuccessful()) {
      return failRes('Payment invalid');
    }

    DB::beginTransaction();
    $this->paymentReference->confirmPayment();

    //== Credit InstitutionGroup Wallet
    $getInstGroup = $this->paymentReference->payable;

    $amount = $this->paymentReference->amount;

    //== Settle any DEBT_WALLET Balance before adding the subplus to the CREDIT_WALLET.
    $prevBal = $getInstGroup->wallet_balance;
    $newBal = $prevBal + $amount;

    $getInstGroup->update([
      'wallet_balance' => $newBal
    ]);

    $funding = Funding::create([
      'funded_by_user_id' => $this->paymentReference->user_id,
      'institution_group_id' => $getInstGroup->id,
      'amount' => $amount,
      'reference' => $this->paymentReference->reference,
      'previous_balance' => $prevBal,
      'new_balance' => $newBal,
      'fundable_id' => $this->paymentReference->id,
      'fundable_type' => $this->paymentReference->getMorphClass()
    ]);

    $funding->transactions()->create([
      'institution_id' => $this->institution->id,
      'institution_group_id' => $getInstGroup->id,
      'amount' => $amount,
      'bbt' => $prevBal,
      'bat' => $newBal,
      'reference' => "funding-{$funding->reference}"
    ]);

    DB::commit();

    return successRes('Payment recorded');
  }
}
