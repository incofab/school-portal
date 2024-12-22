<?php

namespace App\Support\ResultPublications;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Models\PriceList;
use App\Models\ResultPublication;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PublishResultPerTerm extends PublishResult
{
  function getAmountToPay()
  {
    $hasPublishedResults = ResultPublication::where(
      'institution_group_id',
      $this->institutionGroup->id
    )
      ->where('academic_session_id', $this->academicSessionId)
      ->where('term', $this->term)
      ->first();

    $amtToPay = $hasPublishedResults ? 0 : $this->priceList->amount;
    return $amtToPay;
  }
}
