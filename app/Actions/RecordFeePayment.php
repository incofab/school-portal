<?php
namespace App\Actions;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordFeePayment
{
  private $userId;
  private $academicSessionId;
  private $term;
  public function __construct(
    private array $data,
    private Institution $institution
  ) {
    $this->userId = $data['user_id'];
    $this->academicSessionId = $data['academic_session_id'] ?? null;
    $this->term = $data['term'] ?? null;
  }

  /**
   * @param Institution $institution
   * @param array{
   *     reference: string,
   *     fee_id: int,
   *     user_id: int,
   *     amount: float,
   *     academic_session_id?: int|null,
   *     term?: string|null,
   *     method?: string|null
   * } $data
   */
  public static function run(array $data, Institution $institution)
  {
    return (new self($data, $institution))->execute();
  }

  private function execute()
  {
    $fee = Fee::query()->findOrFail($this->data['fee_id']);
    $bindingData = collect($this->data)
      ->only(['fee_id', 'user_id', 'academic_session_id', 'term'])
      ->toArray();
    $bindingData['institution_id'] = $fee->institution_id;

    $feePayment = FeePayment::query()->where($bindingData);

    // If payment is already completed, abort
    if ($feePayment && $feePayment->amount_remaining < 1) {
      return [$feePayment, null];
    }

    $amountPaid = $this->data['amount'] + ($feePayment?->amount_paid ?? 0);
    $amountRemaining = $fee->amount - $amountPaid;

    DB::beginTransaction();
    $receipt = $this->createReceipt($fee);
    $feePayment = FeePayment::query()->updateOrCreate($bindingData, [
      'amount_paid' => $amountPaid,
      'amount_remaining' => $amountRemaining,
      'fee_amount' => $fee->amount,
      'receipt_id' => $receipt->id
    ]);

    $feePaymentTrack = $feePayment->feePaymentTracks()->create([
      'reference' => $this->data['reference'],
      'amount' => $this->data['amount'],
      'confirmed_by_user_id' => currentUser()->id,
      'method' => $this->data['method'] ?? null
    ]);

    $this->updateReceiptRecords($receipt);

    DB::commit();

    return [$feePayment, $feePaymentTrack];
  }

  private function createReceipt(Fee $fee): Receipt
  {
    $student = Student::query()
      ->where('user_id', $this->userId)
      ->with('classification')
      ->firstOrFail();
    $bindingData = [
      'institution_id' => $fee->institution_id,
      'user_id' => $this->userId,
      'receipt_type_id' => $fee->receipt_type_id
    ];
    /** @var Receipt $receipt */
    $receipt = Receipt::query()->firstOrCreate($bindingData, [
      'reference' => Str::uuid(),
      'academic_session_id' => $this->academicSessionId,
      'term' => $this->term,
      'classification_group_id' =>
        $student->classification->classification_group_id,
      'classification_id' => $student->classification->id
    ]);
    return $receipt;
  }

  private function updateReceiptRecords(Receipt $receipt)
  {
    $totalAmount = $receipt
      ->feePayments()
      ->getQuery()
      ->sum('amount_paid');
    $receipt->fill(['total_amount' => $totalAmount])->save();
  }
}
