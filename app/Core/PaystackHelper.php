<?php

namespace App\Core;

use App\DTO\PaymentKeyDto;
use App\Models\Institution;
use App\Support\SettingsHandler;
use Illuminate\Support\Facades\Http;
use App\Enums\Payments\PaymentPurpose;

class PaystackHelper
{
  const PERCENTAGE_CHARGE = 1.5;
  const FLAT_CHARGE = 100;
  const FLAT_CHARGE_ELIGIBLE = 2500;

  function __construct(private PaymentKeyDto $paystackKeys) {}

  static function makeFromInstitution(Institution $institution)
  {
    $paystackKeys = SettingsHandler::make(
      $institution->institutionSettings
    )->getPaystackKeys();
    return new self($paystackKeys);
  }

  static function make()
  {
    $paystackKeys = new PaymentKeyDto(config('services.paystack.public-key'), config('services.paystack.private-key'));
    return new self($paystackKeys);
  }

  function initialize($amount, $email, $callbackUrl, $reference = null, $purpose = null)
  {
    $url = 'https://api.paystack.co/transaction/initialize';

    $privateKey = $this->paystackKeys->getPrivateKey();

    $res = Http::withToken($privateKey)
      ->contentType('application/json')
      ->post($url, [
        'amount' => $amount * 100,
        'email' => $email,
        'callback_url' => $callbackUrl,
        ...$reference ? ['reference' => $reference] : []
      ]);

    if (!$res->json('status')) {
      return failRes($res->json('message', 'Payment initialization failed'));
    }
    if (!$res->json('data.authorization_url')) {
      return failRes(
        $res->json('gateway_response', 'Payment initialization failed')
      );
    }

    return successRes('Payment initialized', [
      'authorization_url' => $res->json('data.authorization_url'),
      'reference' => $res->json('data.reference'),
      'access_code' => $res->json('data.access_code'),
      'result' => $res->json()
    ]);
  }

  //abandoned
  //success
  //
  function verifyReference($reference, $purpose = null)
  {
    $url = 'https://api.paystack.co/transaction/verify/' . $reference;

    $privateKey = $this->paystackKeys->getPrivateKey();

    $res = Http::withToken($privateKey)
      ->contentType('application/json')
      ->get($url);

    if (!$res->json('status')) {
      return failRes($res->json('message', 'Transaction NOT successful'));
    }

    if ($res->json('data.status') !== 'success') {
      return failRes(
        $res->json('gateway_response', 'Transaction NOT successful')
      );
    }

    // Getting here means payment was successful
    $amount = (int) ($res->json('data.amount') / 100);

    return successRes('Payment verifies', [
      'status' => $res->json('data.status'),
      'amount' => $amount,
      'result' => $res->json()
    ]);
  }

  function addPaystackCharge($amount)
  {
    $amount = (int) $amount;
    if (empty($amount)) {
      return 0;
    }

    $finalAmount = $amount;

    if ($amount >= self::FLAT_CHARGE_ELIGIBLE) {
      $finalAmount = $amount + self::FLAT_CHARGE;
    }

    return ceil($finalAmount / (1 - self::PERCENTAGE_CHARGE / 100));
  }

  function removePaystackCharge($chargedAmount)
  {
    $chargedAmount = (int) $chargedAmount;
    if (empty($chargedAmount)) {
      return 0;
    }

    $amount = floor($chargedAmount * (1 - self::PERCENTAGE_CHARGE / 100));

    if ($amount >= self::FLAT_CHARGE_ELIGIBLE) {
      $amount = $amount - self::FLAT_CHARGE;
    }

    return $amount;
  }

  function testPaystackCharges()
  {
    $str = '';
    $i = 0;
    $enteredAmount = 2000;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str = "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 3000;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 5500;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 800;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 'dsk';
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    dd($str);
  }
}
