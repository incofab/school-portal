<?php

namespace App\Support\ResultPublications;

use App\Models\TermResult;

class PublishResultPerStudentPerSession extends PublishResult
{
  function getAmountToPay()
  {
    /** Charge for students who does not have an existing result published this session. */
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
  }
}
