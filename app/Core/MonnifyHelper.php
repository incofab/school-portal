<?php
namespace App\Core;

use App\Enums\Payments\PaymentMerchantType;
use App\Models\Bank;
use App\Models\ReservedAccount;
use App\Models\User;
use App\Support\Res;
use Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Str;

class MonnifyHelper
{
  const PERCENTAGE_CHARGE = 1;
  const CHARGE_START_AMOUNT = 500;
  const CHARGE_STOP_AMOUNT = 10000;

  const BASE_URL = 'https://api.monnify.com/api/'; //'https://sandbox.monnify.com/api/v1/';
  const BASE_URL_SANDBOX = 'https://sandbox.monnify.com/api/';

  static function make()
  {
    return new self();
  }

  function url($prefix)
  {
    return (config('app.debug') ? self::BASE_URL_SANDBOX : self::BASE_URL) .
      $prefix;
  }

  function auth()
  {
    $ret = $this->execCurl($this->url('v1/auth/login'), []);
    if (!$ret->success) {
      return $ret;
    }
    $result = $ret['result'];
    return successRes('Authentication successful', [
      'token' => $result['accessToken']
    ]);
  }

  function updateAccountBvn(
    User $user,
    ReservedAccount $reservedAccount,
    string $type
  ): Res {
    $typeLower = strtolower($type);
    // $auth = $this->auth();
    // if (!$auth->success) {
    //   return $auth;
    // }
    return $this->execCurl(
      $this->url(
        "v1/bank-transfer/reserved-accounts/{$reservedAccount->reference}/kyc-info"
      ),
      [$type => $user[$typeLower]],
      'PUT',
      true
    );
  }

  function reserveAccount(User $user): Res
  {
    $reference = $user->getReference();
    // $auth = $this->auth();
    // if (!$auth->success) {
    //   return $auth;
    // }
    $url = $this->url('v2/bank-transfer/reserved-accounts');
    $data = [
      'accountReference' => $reference,
      'accountName' => ucfirst($user->username) . ' ' . config('app.name'),
      'currencyCode' => 'NGN',
      'contractCode' => config('services.monnify.contract-code'),
      'customerEmail' => $user->email,
      'customerName' => $user->full_name,
      ...$user->bvn ? ['bvn' => $user->bvn] : [],
      ...$user->nin ? ['nin' => $user->nin] : [],
      'getAllAvailableBanks' => true
    ];

    $ret = $this->execCurl($url, $data, 'POST', true);

    if (!$ret->success) {
      return $ret;
    }

    $accounts = $ret->result['accounts'] ?? [];
    $savedAccounts = $this->saveAccounts($accounts, $user, $reference);

    return successRes('account created successfully', [
      'data' => $savedAccounts
    ]);
  }

  private function saveAccounts($accounts, User $user, string $reference)
  {
    $savedAccounts = [];
    foreach ($accounts as $key => $account) {
      $savedAccounts[] = $user->reservedAccounts()->firstOrCreate(
        [
          'merchant' => PaymentMerchantType::Monnify,
          'bank_code' => $account['bankCode']
        ],
        [
          'reference' => $reference,
          'account_name' => $account['accountName'],
          'account_number' => $account['accountNumber'],
          'bank_name' => $account['bankName']
        ]
      );
    }
    return $savedAccounts;
  }

  function getReservedAccounts(User $user, bool $generateNew = true)
  {
    $reference = $user->getReference();
    // $auth = $this->auth();
    // if (!$auth->success) {
    //   return $auth;
    // }

    $url = $this->url('v2/bank-transfer/reserved-accounts/' . $reference);
    $res = $this->execCurl($url, [], 'GET', true);

    if (!$res->isSuccessful()) {
      return $generateNew ? $this->reserveAccount($user) : $res;
    }
    $accounts = $res->result['accounts'] ?? [];
    $savedAccounts = $this->saveAccounts($accounts, $user, $reference);
    return successRes('account created successfully', [
      'data' => $savedAccounts
    ]);
  }

  function getTransactionStatus($reference)
  {
    $url = $this->url(
      'v2/merchant/transactions/query?paymentReference=' . urlencode($reference)
    );
    $res = $this->execCurl($url, [], 'GET');
    if (!$res->isSuccessful()) {
      return $res;
    }
    if (
      in_array(Arr::get($res->result, 'paymentStatus'), [
        'PARTIALLY_PAID',
        'PAID',
        'OVERPAID'
      ])
    ) {
      return $res;
    }
    return failRes($res->getMessage(), $res->result);
  }

  function listBanks()
  {
    $url = $this->url('v1/banks');
    $res = $this->execCurl($url, [], 'GET', true);
    if (!$res->isSuccessful()) {
      return $res;
    }

    $banks = $res->result;
    foreach ($banks as $key => $bank) {
      Bank::query()->firstOrCreate(
        ['bank_code' => $bank['code']],
        ['bank_name' => $bank['name']]
      );
    }
    return successRes('Banks recorded');
  }

  function validateBankAccount($bankCode, $accountNumber)
  {
    $url = $this->url('v1/disbursements/account/validate');
    $res = $this->execCurl(
      "$url?" .
        http_build_query([
          'bankCode' => $bankCode,
          'accountNumber' => $accountNumber
        ]),
      'GET',
      true
    );
    if (!$res->isSuccessful()) {
      return $res;
    }
    $result = $res->result;
    return successRes('Banks recorded', [
      'account_number' => $result['accountNumber'],
      'account_name' => $result['accountName'],
      'bank_code' => $result['bankCode']
    ]);
  }

  function getCharge($amount)
  {
    $amount = (int) $amount;

    $charge = ceil((self::PERCENTAGE_CHARGE / 100) * $amount);

    if ($charge < 50 && $amount > 1000) {
      return 50;
    }

    if ($charge < 20) {
      return 20;
    }

    return $charge > 100 ? 100 : $charge;
  }

  function execCurl($url, $data, $method = 'POST', bool $useAuth = false)
  {
    $token = null;
    if ($useAuth) {
      $auth = $this->auth();
      if (!$auth->success) {
        return $auth;
      }
      $token = $auth->token;
    }

    try {
      $http = Http::when(
        $token,
        fn(PendingRequest $http) => $http->withToken($token),
        fn(PendingRequest $http) => $http->withBasicAuth(
          config('services.monnify.public'),
          config('services.monnify.secret')
        )
      );
      if ($method === 'POST') {
        $res = $http->post($url, $data);
      } elseif ($method === 'PUT') {
        $res = $http->put($url, $data);
      } else {
        $res = $http->get($url);
      }
      // info([$url => $res->json(), 'token' => $token]);
      if (!$res->successful() || !$res->json('requestSuccessful')) {
        return failRes($res->json('responseMessage') ?? 'Operation failed');
      }
      return successRes('', ['result' => $res->json('responseBody')]);
    } catch (\Throwable $th) {
      return failRes('Errro processing data');
    }
  }

  function test()
  {
    $str = '';
    //         $i = 0;
    //         $enteredAmount = 2000;
    //         $addCharge = $this->addPaystackCharge($enteredAmount);
    //         $removeCharge = $this->removePaystackCharge($addCharge);
    //         $i++;
    //         $str = "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    //         $str .= '<br /><br />';
    echo '<br />Charge 200 = ' . $this->getCharge(200);
    echo '<br />Charge 500 = ' . $this->getCharge(500);
    echo '<br />Charge 501 = ' . $this->getCharge(501);
    echo '<br />Charge 1000 = ' . $this->getCharge(1000);
    echo '<br />Charge 10000 = ' . $this->getCharge(10000);
    die('Done');
  }
}
