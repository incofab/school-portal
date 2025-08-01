<?php
namespace App\Actions\Payments;

use App\Enums\ReceiptStatus;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeePaymentHandler
{
  public function __construct(private Institution $institution)
  {
  }

  static function make(Institution $institution)
  {
    return new self($institution);
  }

  /**
   * @param array{
   *  reference: string,
   *  user_id?: int|null,
   *  amount: float,
   *  method?: string
   * } $data
   */
  function create(
    array $data,
    Fee $fee,
    ?Model $payable = null,
    ?User $staff = null,
    ?bool $allowOverPayment = false
  ) {
    $userId = $data['user_id'] ?? null;

    $bindingData = [
      'fee_id' => $fee->id,
      'user_id' => $userId,
      'academic_session_id' => $fee->academic_session_id,
      'term' => $fee->term
    ];

    $receipt = Receipt::query()
      ->where($bindingData)
      ->with('feePayments')
      ->first();

    $amount = $data['amount'];
    $amountPaid = $receipt?->paymentsSum() + $amount;
    $amountRemaining = $fee->amount - $amountPaid;

    if ($amountRemaining < 0 && !$allowOverPayment) {
      return throw ValidationException::withMessages([
        'amount' => 'Overpayment not allowed'
      ]);
    }

    DB::beginTransaction();
    $receipt = Receipt::query()->updateOrCreate($bindingData, [
      'amount_paid' => $amountPaid,
      'amount' => $fee->amount,
      'amount_remaining' => $amountRemaining,
      'amount_paid' => $amountPaid,
      'institution_id' => $this->institution->id,
      'status' =>
        $amountRemaining > 0 ? ReceiptStatus::Partial : ReceiptStatus::Paid
    ]);

    $feePayment = FeePayment::query()->firstOrCreate(
      ['reference' => $data['reference']],
      [
        ...collect($data)
          ->except('user_id', 'academic_session_id', 'term')
          ->toArray(),
        'fee_id' => $fee->id,
        'institution_id' => $this->institution->id,
        'receipt_id' => $receipt->id,
        'payable_id' => $payable?->id,
        'payable_type' => $payable?->getMorphClass(),
        'confirmed_by_user_id' => $staff?->id
      ]
    );

    DB::commit();

    return [$receipt, $feePayment];
  }

  static function getReceipt(Fee $fee, User $user): Receipt|null
  {
    $bindingData = [
      'fee_id' => $fee->id,
      'user_id' => $user->id,
      'academic_session_id' => $fee->academic_session_id,
      'term' => $fee->term
    ];
    return Receipt::query()
      ->where($bindingData)
      ->first();
  }

  function delete(FeePayment $feePayment)
  {
    $receipt = $feePayment->receipt;
    $fee = $feePayment->fee;
    $amount = $feePayment->amount;
    $amountPaid = $receipt->paymentsSum() - $amount;
    $amountRemaining = $fee->amount - $amountPaid;
    $attr = [
      'amount_paid' => $amountPaid,
      'amount' => $fee->amount,
      'amount_remaining' => $amountRemaining
    ];
    DB::beginTransaction();
    $receipt->fill($attr)->save();
    $feePayment->delete();
    DB::commit();
  }
}
