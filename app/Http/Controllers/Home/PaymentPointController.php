<?php

namespace App\Http\Controllers\Home;

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\ReservedAccount;
use App\Support\UserTransactionHandler;
use Illuminate\Http\Request;

class PaymentPointController extends Controller
{
  function webhook(Request $request)
  {
    if (!config('app.debug')) {
      $secretKey = config('services.payment-point.secret');

      // Step 1: Read the raw POST data from the request body
      $inputData = file_get_contents('php://input');

      // Step 2: Get the signature from the headers
      $signatureHeader = $_SERVER['HTTP_PAYMENTPOINT_SIGNATURE'];
      // Step 3: Calculate the expected signature using HMAC-SHA256
      $calculatedSignature = hash_hmac('sha256', $inputData, $secretKey);

      // Step 4: Verify if the calculated signature matches the signature from the header
      if (!hash_equals($calculatedSignature, $signatureHeader)) {
        return response()->json('Invalid signature.', 400);
      }
    }
    // Step 5: Decode the JSON payload
    $webhookData = $request->all(); // json_decode($inputData, true);

    // Step 6: Ensure the data was successfully decoded
    if ($webhookData === null) {
      return response()->json('Invalid JSON data received.', 400);
    }

    // Step 7: Extract relevant data from the decoded webhook
    $transactionId = $webhookData['transaction_id'] ?? null;
    $amountPaid = $webhookData['amount_paid'] ?? null;
    $settlementAmount = $webhookData['settlement_amount'] ?? null;
    $status = $webhookData['transaction_status'] ?? null;

    // Check if required data is present
    if (!$transactionId || !$amountPaid || !$settlementAmount || !$status) {
      return response()->json('Missing required data.', 400);
    }

    // Step 9: Respond with a 200 OK status to acknowledge receipt of the webhook

    $reference = $webhookData['transaction_id'];
    $bankAccount = ReservedAccount::query()
      ->where('merchant', PaymentMerchantType::PaymentPoint)
      ->where('bank_name', $webhookData['receiver']['bank'] ?? '')
      ->where(
        'account_number',
        $webhookData['receiver']['account_number'] ?? ''
      )
      ->with('reservable')
      ->firstOrFail();

    abort_if(!$bankAccount, 200, 'Account not found');

    $entity = $bankAccount->reservable;
    UserTransactionHandler::recordTransaction(
      amount: $settlementAmount,
      entity: $entity,
      transactionType: TransactionType::Credit,
      transactionable: $bankAccount,
      reference: $reference,
      isVerified: true
    );

    return response()->json('okay');
  }
}
