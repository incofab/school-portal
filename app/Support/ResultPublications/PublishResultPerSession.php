<?php

namespace App\Support\ResultPublications;

use App\Models\ResultPublication;

class PublishResultPerSession extends PublishResult
{
  function getAmountToPay()
  {
    $hasPublishedResults = $this->getResultPublication();
    $amtToPay = $hasPublishedResults ? 0 : $this->priceList->amount;
    return $amtToPay;
  }

  function getResultPublication(): ?ResultPublication
  {
    return ResultPublication::query()
      ->where($this->resultPublicationBindingData)
      ->first();
  }
}
