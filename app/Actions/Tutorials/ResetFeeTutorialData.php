<?php

namespace App\Actions\Tutorials;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\PaymentReference;
use App\Models\Receipt;

class ResetFeeTutorialData
{
  const DEMO_FEE_TITLE = 'PTA Development Levy';

  /**
   * Deletes every fee/payment/bank-account record the fee-payment tutorial
   * creates for the given demo institution, scoped entirely to that one
   * institution so it never touches real school data. Shared by
   * `tutorial:seed-fee-demo` (resets before generating a recording) and
   * `tutorial:clear-fee-demo` (tears down after generating one), so no
   * fee/payment demo data is left sitting in the database between runs.
   */
  public static function run(Institution $institution): void
  {
    $institution->institutionGroup
      ->bankAccounts()
      ->withTrashed()
      ->forceDelete();
    ManualPayment::withTrashed()
      ->where('institution_id', $institution->id)
      ->forceDelete();
    Receipt::withTrashed()
      ->where('institution_id', $institution->id)
      ->forceDelete();
    FeePayment::withTrashed()
      ->where('institution_id', $institution->id)
      ->forceDelete();
    PaymentReference::where('institution_id', $institution->id)->delete();
    Fee::withTrashed()
      ->where('institution_id', $institution->id)
      ->where('title', self::DEMO_FEE_TITLE)
      ->forceDelete();
  }
}
