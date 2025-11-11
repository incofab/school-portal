<?php

namespace App\Support\ResultPublications;

class PublishResultPerStudentPerTerm extends PublishResult
{
  function getAmountToPay()
  {
    // return $this->resultsToPublish->count() * $this->priceList->amount;
    $remainingStudents =
      $this->numOfStudents -
      intval($this->getResultPublication()?->num_of_students);

    if ($remainingStudents < 0) {
      $remainingStudents = 0;
    }

    return $remainingStudents * $this->priceList->amount;
  }
}
