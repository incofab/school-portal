<?php
namespace App\Actions\Payments;

use App\Models\Fee;
use App\Models\Institution;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecordMultiFeePayments
{
  public function __construct(
    private array $data,
    private Institution $institution
  ) {
  }

  /**
   * @param Institution $institution
   * @param array{
   *     user_id: int,
   *     academic_session_id?: int|null,
   *     term?: string|null,
   *     method?: string|null
   *     transaction_reference?: string|null,
   *     fee_ids: int[]
   * } $data
   */
  public static function run(array $data, Institution $institution)
  {
    return (new self($data, $institution))->execute();
  }

  private function execute()
  {
    /** @var Collection<string,Fee> */
    $fees = Fee::query()
      ->whereIn('id', $this->data['fee_ids'])
      ->get();
    foreach ($fees as $key => $fee) {
      RecordFeePayment::run(
        [
          ...collect($this->data)
            ->only([
              'user_id',
              'academic_session_id',
              'term',
              'method',
              'transaction_reference'
            ])
            ->toArray(),
          'fee_id' => $fee->id,
          'reference' => Str::orderedUuid(),
          'amount' => $fee->amount
        ],
        $this->institution
      );
    }
  }
}
