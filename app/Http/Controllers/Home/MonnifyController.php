<?php

namespace App\Http\Controllers\Home;

use App\Core\MonnifyHelper;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
use App\Models\ReservedAccount;
use App\Support\Payments\Processors\PaymentProcessor;
use App\Support\UserTransactionHandler;
use Illuminate\Http\Request;

class MonnifyController extends Controller
{
  public function __construct()
  {
  }

  public function callback(Request $request)
  {
    return $this->handleReference($request->reference);
  }

  public function checkout(Request $request)
  {
    $request->validate([
      'reference' => ['required', 'string']
    ]);
    $paymentReference = PaymentReference::query()
      ->where('reference', $request->reference)
      ->where('merchant', PaymentMerchantType::Monnify)
      ->with('user', 'institution', 'payable', 'paymentable')
      ->firstOrFail();

    return view('home.monnify-checkout', [
      'paymentReference' => $paymentReference
    ]);
  }

  public function verifyReference(Request $request)
  {
    $request->validate(['reference' => 'required']);

    return $this->handleReference($request->reference);
  }

  public function webhook(Request $request)
  {
    $allowedIp = '35.242.133.146';
    $clientIp = $request->ip();

    // $body = @file_get_contents('php://input');
    // $post = json_decode($body, true);
    $post = $request->all();

    if (!config('app.debug') && $clientIp !== $allowedIp) {
      info([
        'message' => "Monnify webhook accessed from authorized IP address ($clientIp)",
        'content' => $post
      ]);
      abort(403, 'Unauthorized Access'); // Respond with a 403 Forbidden error
    }
    $post = $post['eventData'];
    // info($body);

    $transactionReference = $post['paymentReference'];
    $reference = $post['paymentReference'];
    $settlementAmount = $post['settlementAmount'];
    $amountPaid = $post['amountPaid'];
    $paidOn = $post['paidOn'];
    $accountReference = $post['product']['reference'];
    $productType = $post['product']['type'];

    $transactionHash = $post['transactionHash'];

    $recreatedHash = hash(
      'SHA512',
      config('services.monnify.secret') .
        "|$reference|$amountPaid|$paidOn|$transactionReference"
    );

    if (
      !is_string($transactionHash) ||
      !hash_equals($recreatedHash, $transactionHash)
    ) {
      info([
        'Error' => 'Hash mismatch',
        'Data' => $post,
        'transactionHash' => $transactionHash,
        'recreatedHash' => $recreatedHash
      ]);
      return response()->json('Hash mismatch', 400);
    }

    if ($productType !== 'RESERVED_ACCOUNT') {
      return $this->handleReference($reference);
    }

    $res = MonnifyHelper::make()->getTransactionStatus($reference);
    abort_unless($res->isSuccessful(), 200, $res->message);

    $settlementAmount = $res->amount;
    $destinationAccountInformation = $post['destinationAccountInformation'];
    /** @var ReservedAccount $reservedAccount */
    $reservedAccount = ReservedAccount::query()
      ->where('reference', $accountReference)
      ->where('bank_code', $destinationAccountInformation['bankCode'])
      ->where('account_number', $destinationAccountInformation['accountNumber'])
      ->with('reservable')
      ->first();

    abort_if(!$reservedAccount, 200, 'Account not found');

    $entity = $reservedAccount->reservable;
    UserTransactionHandler::recordTransaction(
      amount: $settlementAmount,
      entity: $entity,
      transactionType: TransactionType::Credit,
      transactionable: $reservedAccount,
      reference: $reference,
      isVerified: true
    );

    return response()->json('okay');
  }

  private function handleReference($reference)
  {
    $paymentRef = PaymentReference::query()
      ->where('reference', $reference)
      ->with('user', 'institution')
      ->firstOrFail();

    $paymentProcessor = PaymentProcessor::make($paymentRef);

    $res = $paymentProcessor->processPaymentWithTransaction();

    return redirect($paymentRef->redirect_url ?? route('home'))->with(
      $res->isSuccessful() ? 'message' : 'error',
      $res->message
    );
  }
}
