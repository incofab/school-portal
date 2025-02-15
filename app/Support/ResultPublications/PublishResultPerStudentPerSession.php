<?php

namespace App\Support\ResultPublications;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Models\PriceList;
use App\Models\ResultPublication;
use App\Models\TermResult;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
