<?php

namespace App\Support\ResultPublications;

use Illuminate\Database\Eloquent\Model;

class PublishResultPerStudentPerTerm extends PublishResult
{
  function getAmountToPay()
  {
    return $this->resultsToPublish->count() * $this->priceList->amount;
  }
}
