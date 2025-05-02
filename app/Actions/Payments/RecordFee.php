<?php
namespace App\Actions\Payments;

use App\Models\Fee;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;

class RecordFee
{
  /**
   * @param Institution $institution
   * @param array{
   *     title: string,
   *     amount: float,
   *     payment_interval: string,
   *     academic_session_id: int,
   *     term?: string,
   *     fee_items?: array|null,
   *     fee_categories: array,
   * } $data
   */
  public function __construct(
    private array $data,
    private Institution $institution,
    private ?Fee $fee
  ) {
  }

  public static function run(
    array $data,
    Institution $institution,
    ?Fee $fee = null
  ) {
    return (new self($data, $institution, $fee))->execute();
  }

  private function execute()
  {
    $feeData = collect($this->data)
      ->except('fee_categories')
      ->toArray();

    DB::beginTransaction();

    if ($this->fee) {
      $this->fee->fill($feeData)->save();
    } else {
      $this->fee = Fee::query()->create([
        ...$feeData,
        'institution_id' => $this->institution->id
      ]);
    }

    $suppliedFeeCategories = collect($this->data['fee_categories']);

    $existingFeeCategories = $this->fee->feeCategories()->get();

    $feeCategoriesToDelete = $existingFeeCategories->filter(function (
      $item
    ) use ($suppliedFeeCategories) {
      return $suppliedFeeCategories->first(
        fn($e) => $e['feeable_id'] == $item['feeable_id'] &&
          $e['feeable_type'] == $item['feeable_type']
      ) == null;
    });

    $this->fee
      ->feeCategories()
      ->whereIn('feeable_type', $feeCategoriesToDelete->pluck('feeable_type'))
      ->whereIn('feeable_id', $feeCategoriesToDelete->pluck('feeable_id'))
      ->forceDelete();

    foreach ($suppliedFeeCategories as $key => $suppliedFeeCategory) {
      $this->fee
        ->feeCategories()
        ->firstOrCreate([
          ...$suppliedFeeCategory,
          'institution_id' => $this->fee->institution_id
        ]);
    }

    DB::commit();

    return $this->fee;
  }
}
