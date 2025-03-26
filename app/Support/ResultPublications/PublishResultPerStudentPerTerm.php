<?php

namespace App\Support\ResultPublications;

class PublishResultPerStudentPerTerm extends PublishResult
{
  function getAmountToPay()
  {
    return $this->resultsToPublish->count() * $this->priceList->amount;
  }
}
