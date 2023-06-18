<?php
namespace App\Actions;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;

class RecordFeePayment
{
  public function __construct(
    private array $data,
    private Institution $institution
  ) {
  }

  public static function run(array $data, Institution $institution)
  {
    return (new self($data, $institution))->execute();
  }

  private function execute()
  {
    DB::beginTransaction();

    $fee = Fee::query()->findOrFail($this->data['fee_id']);
    $feePayment = $this->institution
      ->feePayments()
      ->where('fee_id', $this->data['fee_id'])
      ->where('user_id', $this->data['user_id'])
      ->when(
        $this->data['academic_session_id'],
        fn($q, $value) => $q->where('academic_session_id', $value)
      )
      ->when($this->data['term'], fn($q, $value) => $q->where('term', $value))
      ->first();

    $amountPaid = $this->data['amount'] + ($feePayment?->amount_paid ?? 0);
    $amountRemaining = $fee->amount - $amountPaid;

    $bindingData = collect($this->data)
      ->only(['fee_id', 'user_id', 'academic_session_id', 'term'])
      ->toArray();
    $bindingData['institution_id'] = $fee->institution_id;
    $valuesData = [
      'amount_paid' => $amountPaid,
      'amount_remaining' => $amountRemaining,
      'fee_amount' => $fee->amount
    ];

    $feePayment = FeePayment::query()->updateOrCreate(
      $bindingData,
      $valuesData
    );

    $feePaymentTrack = $feePayment->feePaymentTracks()->create([
      'reference' => $this->data['reference'],
      'amount' => $this->data['amount'],
      'confirmed_by_user_id' => currentUser()->id,
      'method' => $this->data['method'] ?? null
    ]);

    DB::commit();

    return [$feePayment, $feePaymentTrack];
  }
}
