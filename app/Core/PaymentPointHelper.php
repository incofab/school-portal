<?php
namespace App\Core;

use App\Enums\Payments\PaymentMerchantType;
use App\Models\User;
use App\Support\Res;
use Http;

class PaymentPointHelper
{
  const BASE_URL = 'https://api.paymentpoint.co/api/v1/';

  static function make()
  {
    return new self();
  }

  function url($prefix)
  {
    return self::BASE_URL . $prefix;
    // return (config('app.debug') ? self::BASE_URL_SANDBOX : self::BASE_URL) .
    //     $prefix;
  }

  function auth()
  {
    return Http::withToken(
      config('services.payment-point.secret')
    )->withHeaders([
      'Content-Type' => 'application/json',
      'api-key' => config('services.payment-point.api-key')
    ]);
  }

  function reserveAccount(User $userData): Res
  {
    $url = $this->url('createVirtualAccount');
    $data = [
      'email' => $userData->email,
      'name' => $userData->firstname . ' ' . $userData->lastname,
      'phoneNumber' => $userData->phone,
      'bankCode' => ['20946'],
      'businessId' => config('services.payment-point.business-id')
    ];

    $res = $this->auth()->post($url, $data);
    // info($res->json());
    $bankAccounts = $res->json('bankAccounts');
    if (
      !$res->successful() ||
      $res->json('status') !== 'success' ||
      empty($bankAccounts)
    ) {
      return failRes($res->json('message') ?? 'Operation failed');
    }

    foreach ($bankAccounts as $key => $bankAccount) {
      $userData->reservedAccounts()->firstOrCreate(
        [
          'reference' => $bankAccount['Reserved_Account_Id']
        ],
        [
          'merchant' => PaymentMerchantType::Monnify,
          'account_name' => $bankAccount['accountName'],
          'account_number' => $bankAccount['accountNumber'],
          'bank_name' => $bankAccount['bankName'],
          'status' => 'ACTIVE'
        ]
      );
    }

    return successRes('account created successfully');
  }
}
