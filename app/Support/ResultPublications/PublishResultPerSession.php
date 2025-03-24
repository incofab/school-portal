<?php

namespace App\Support\ResultPublications;

use App\Models\ResultPublication;

class PublishResultPerSession extends PublishResult
{
  function getAmountToPay()
  {
    $hasPublishedResults = ResultPublication::where(
      'institution_group_id',
      $this->institutionGroup->id
    )
      ->where('academic_session_id', $this->academicSessionId)
      ->first();

    $amtToPay = $hasPublishedResults ? 0 : $this->priceList->amount;
    return $amtToPay;
  }
}
