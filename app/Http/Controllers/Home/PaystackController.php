<?php

namespace App\Http\Controllers\Home;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\PaymentReference;
use App\Support\SettingsHandler;
use App\Http\Controllers\Controller;
use App\Enums\Payments\PaymentStatus;
use App\Support\Payments\Processors\PaymentProcessor;

class PaystackController extends Controller
{
  public function __construct()
  {
  }

  public function callback(Request $request)
  {
    return $this->handleReference($request->reference);
  }

  function verifyReference(Request $request)
  {
    $reference = $request->reference;
    if (empty($reference)) {
      die('Reference not supplied');
    }
    return $this->handleReference($reference);
  }

  function webhook()
  {
    if (
      strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' ||
      !array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)
    ) {
      info('paystackWebhook: Method not post or Signature not found');
      exit();
    }

    // Retrieve the request's body
    $input = @file_get_contents('php://input');

    // validate event do all at once to avoid timing attack
    // if (
    //   Arr::get($_SERVER, 'HTTP_X_PAYSTACK_SIGNATURE') !==
    //   hash_hmac('sha512', $input, config('services.paystack.secret-key'))
    // ) {
    //   info('paystackWebhook: Signature validation failed');
    //   exit();
    // }

    http_response_code(200);

    // parse event (which is json string) as object
    // Do something - that will not take long - with $event
    $event = json_decode($input, true);

    abort_if(Arr::get($event, 'event') != 'charge.success', 403);

    $data = Arr::get($event, 'data');

    abort_if(Arr::get($data, 'status') != 'success', 403);

    $reference = $data['reference'] ?? '';

    $paymentReference = PaymentReference::query()
      ->where('reference', $reference)
      ->with('institution.institutionSettings')
      ->firstOrFail();

    $paystackKeys = SettingsHandler::make(
      $paymentReference->institution->institutionSettings
    )->getPaystackKeys();

    $this->validateSignatureKey($input, $paystackKeys->getPrivateKey());

    return $this->handleReference($reference);
  }

  private function validateSignatureKey($input, $secretKey)
  {
    // validate event do all at once to avoid timing attack
    if (
      Arr::get($_SERVER, 'HTTP_X_PAYSTACK_SIGNATURE') !==
      hash_hmac('sha512', $input, $secretKey)
    ) {
      info('paystackWebhook: Signature validation failed');
      exit();
    }
  }

  private function handleReference($reference)
  {
    $paymentRef = PaymentReference::query()
      ->where('reference', $reference)
      ->with('user', 'institution')
      ->firstOrFail();

    abort_unless(
      $paymentRef->status === PaymentStatus::Pending,
      403,
      'Paymet already processed'
    );

    $paymentProcessor = PaymentProcessor::make($paymentRef);

    $res = $paymentProcessor->processPayment();

    return redirect($paymentRef->redirect_url ?? route('home'))->with(
      $res->isSuccessful() ? 'message' : 'error',
      $res->message
    );
  }
}
