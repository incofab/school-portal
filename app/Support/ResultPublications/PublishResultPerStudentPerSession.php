<?php

namespace App\Support\ResultPublications;

use App\Models\ResultPublication;

class PublishResultPerStudentPerSession extends PublishResult
{
  function getAmountToPay()
  {
    $remainingStudents =
      $this->numOfStudents -
      intval($this->getResultPublication()?->num_of_students);

    if ($remainingStudents < 0) {
      $remainingStudents = 0;
    }

    return $remainingStudents * $this->priceList->amount;

    /* Charge for students who does not have an existing result published this session. 
    $studentIds = $this->resultsToPublish->pluck('student_id')->toArray();

    // Fetch students that has at least 1 published result in this session.
    $hasPublishedResults = TermResult::whereIn('student_id', $studentIds)
      ->where('academic_session_id', $this->academicSessionId)
      ->whereNotNull('result_publication_id')
      ->distinct('student_id')
      ->get();

    $publishedResultsCount = count($hasPublishedResults);
    $resultToPublisCount = $this->resultsToPublish->count();

    $amtToPay =
      ($resultToPublisCount - $publishedResultsCount) *
      $this->priceList->amount;

    return $amtToPay < 0 ? 0 : $amtToPay;
    */
  }

  function getResultPublication(): ?ResultPublication
  {
    return ResultPublication::query()
      ->where($this->resultPublicationBindingData)
      ->first();
  }
}
