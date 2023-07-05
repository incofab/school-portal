<?php
namespace App\Helpers;

use Illuminate\Support\Arr;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use App\Models\PaymentReference;
use App\Models\Deposit;

class DepositHelper
{
  private $depositModel;
  private $paystackHelper;
  private $raveHelper;

  function __construct(
    \App\Models\Deposit $depositModel,
    \App\Core\PaystackHelper $paystackHelper,
    \App\Core\RaveHelper $raveHelper
  ) {
    $this->depositModel = $depositModel;

    $this->paystackHelper = $paystackHelper;

    $this->raveHelper = $raveHelper;
  }

  function initCardDeposit($post, User $user)
  {
    $amount = Arr::get($post, 'amount', 0);
    $paymentPlatform = Arr::get($post, 'select-payment-platform');

    if ($paymentPlatform === MERCHANT_RAVE) {
      $callbackUrl = route('rave-callback');
      $reference = PaymentReference::generateReferece(
        $user->username ?? 'rave'
      );
      $initRet = $this->raveHelper->initialize(
        $user,
        $amount,
        $callbackUrl,
        $reference
      );
    } else {
      $email = empty($user->email) ? SITE_EMAIL : $user->email;

      $callbackUrl = route('paystack-callback');

      $initRet = $this->paystackHelper->initialize(
        $amount,
        $email,
        $callbackUrl
      );
    }

    if (!$initRet[SUCCESSFUL]) {
      return $initRet;
    }

    $reference = $initRet['reference'];

    $post = [
      'reference' => $reference,
      'merchant' => $paymentPlatform,
      'payment_type' => Deposit::class,
      'payment_id' => null,
      'amount' => $amount
    ];

    $ret = PaymentReference::insert($user->id, $post, null);
    //         $ret = \App\Models\PaymentReference::insert($user->id, $reference, $paymentPlatform, null, \App\Models\Deposit::class);
    $ret['reference'] = $reference;
    $ret['amount'] = $amount;
    return $initRet;
  }

  function quickDeposit($post, User $user, $adminData)
  {
    $post['payment_method'] = 'Quick Deposit';
    $post['depositor_name'] = Arr::get(
      $post,
      'depositor_name',
      "{$user['name']}"
    );
    $post['choice_platform'] = CHOICE_PLATFORM_WEBSITE;
    $post['transaction_entry'] =
      \App\Models\Transaction::TRANSACTION_ENTRY_CREDIT;
    $post['transaction_type'] =
      \App\Models\Transaction::TRANSACTION_TYPE_BANK_DEPOSIT;
    $post['bbt'] = $user['balance'];

    if (Arr::get($post, 'amount') < 100) {
      return ret(
        false,
        'Deposit amount cannot be below ' . CURRENCY_SIGN . '100'
      );
    }

    if (Arr::get($post, 'amount') < MINIMUM_DEPOSIT) {
      $post['charge'] = MINIMUM_DEPOSIT_CHARGE;
    }

    $ret = \App\Models\Transaction::insert($post, $user, $this->depositModel);

    if (!$ret[SUCCESSFUL]) {
      return $ret;
    }

    $transaction = $ret['data'];

    //         $ret = $this->depositModel->confirmUserDeposit(
    //             $this->transactionModel, $transaction[TABLE_ID], $adminData, $this->dataOrderHelper);

    return $ret;
  }

  function recordCardPayment(
    \App\Models\PaymentReference $paymentRef,
    $amount,
    $merchant = MERCHANT_PAYSTACK
  ) {
    $userData = $paymentRef['user'];

    if ($merchant === MERCHANT_PAYSTACK) {
      $amount = $this->paystackHelper->removePaystackCharge($amount);
    } else {
      $amount = $this->raveHelper->removeCharge($amount);
    }

    $post = [];
    $post['choice_platform'] = CHOICE_PLATFORM_WEBSITE;
    $post['depositor_name'] = Arr::get($userData, 'name', 'email');
    $post['amount'] = $amount;
    $post['status'] = 'credited';
    $post['reference'] = $paymentRef['reference'];
    $post['payment_method'] = 'Credit Card';
    //         $post[BANK_NAME] = ucfirst($merchant);
    $post['merchant'] = $merchant;
    $post['channel'] = $merchant;
    $post['transaction_entry'] =
      \App\Models\Transaction::TRANSACTION_ENTRY_CREDIT;
    $post['transaction_type'] =
      \App\Models\Transaction::TRANSACTION_TYPE_BANK_DEPOSIT;

    $post['bbt'] = $userData['balance'];
    $post['bat'] = $userData['balance'] + $amount;

    $transactionRet = \App\Models\Transaction::insert(
      $post,
      $userData,
      $this->depositModel
    );

    if (!Arr::get($transactionRet, SUCCESSFUL)) {
      return $transactionRet;
    }

    $transaction = $transactionRet['data'];

    $userData->creditUser($amount);

    $paymentRef['status'] = 'credited';
    $paymentRef->save();

    return [
      SUCCESSFUL => true,
      MESSAGE => 'Account credited successfully',
      'balance' => $userData['balance']
    ];
  }

  function listDeposits(
    $status = 'all',
    $userId = null,
    $num = 100,
    $page = 1,
    $lastIndex = 0
  ) {
    $allRecords = \App\Models\Transaction::where(
      'transaction_type',
      '=',
      \App\Models\Transaction::TRANSACTION_TYPE_BANK_DEPOSIT
    );

    if ($userId) {
      $allRecords = $allRecords->where('user_id', '=', $userId);
    }

    if ($status !== 'all') {
      $allRecords = $allRecords->where('status', '=', $status);
    }

    if ($lastIndex != 0) {
      $allRecords = $allRecords->where('id', '<', $lastIndex);
    } else {
      $allRecords = $allRecords->skip($num * ($page - 1));
    }

    $allRecords = $allRecords
      ->with(['deposit', 'user'])
      ->orderBy('id', 'DESC')
      ->skip($num * ($page - 1))
      ->take($num)
      ->get();

    $count = (new \App\Models\Transaction())->getTransactionCount(
      $userId,
      \App\Models\Transaction::TRANSACTION_TYPE_BANK_DEPOSIT,
      null
    );

    return [
      'deposits' => $allRecords,
      'count' => $count
    ];
  }
}
