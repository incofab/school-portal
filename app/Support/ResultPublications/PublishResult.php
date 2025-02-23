<?php

namespace App\Support\ResultPublications;

use Eloquent;
use Exception;
use App\Models\User;
use App\Support\Res;
use App\Models\Funding;
use App\Enums\WalletType;
use App\Models\PriceList;
use App\Models\TermResult;
use App\Models\Institution;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Enums\TransactionType;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Support\SettingsHandler;
use App\Models\ResultPublication;
use Illuminate\Database\Eloquent\Model;
use App\Support\Fundings\FundingHandler;
use App\Enums\PriceLists\PaymentStructure;

abstract class PublishResult
{
  protected $resultsToPublish;
  protected $academicSessionId;
  protected $term;
  protected InstitutionGroup $institutionGroup;
  function __construct(
    protected User $staffUser,
    protected Institution $institution,
    protected SettingsHandler $settingHandler,
    protected PriceList $priceList,
    array $submittedClassIds
  ) {
    $this->institutionGroup = $priceList->institutionGroup;
    $this->academicSessionId = $this->settingHandler->getCurrentAcademicSession();
    $this->term = $settingHandler->getCurrentTerm();

    $this->resultsToPublish = TermResult::whereIn(
      'classification_id',
      $submittedClassIds
    )
      ->where('academic_session_id', $this->academicSessionId)
      ->where('term', $this->term)
      ->whereNull('result_publication_id')
      ->get();
  }

  abstract function getAmountToPay();

  function execute(): Res
  {
    $resultsToPublishCount = $this->resultsToPublish->count();

    if ($resultsToPublishCount <= 0) {
      return failRes('No unpublished result found');
    }

    $amountToPay = $this->getAmountToPay();
    if ($amountToPay > 0) {
      if ($this->institutionGroup->credit_wallet < $amountToPay) {
        $loanAmountNeeded =
          $amountToPay - $this->institutionGroup->credit_wallet;

        if (!$this->institutionGroup->canGetLoan($loanAmountNeeded)) {
          return failRes(
            'Insufficient Credit Balance. ₦' .
              number_format($amountToPay) .
              ' Required.'
          );
        }

        $data = [
          'amount' => $loanAmountNeeded,
          'reference' => Str::orderedUuid(),
          'remark' => 'Result Publication'
        ];

        $obj = new FundingHandler(
          $this->institutionGroup,
          $this->staffUser,
          $data
        );
        $obj->requestDebt();
      }

      //===
      $this->institutionGroup
        ->fill([
          'credit_wallet' =>
            $this->institutionGroup->credit_wallet - $amountToPay
        ])
        ->save();
    }

    //== Add record to 'result_publications' DB table
    $publication = ResultPublication::create([
      'institution_id' => $this->institution->id,
      'institution_group_id' => $this->institutionGroup->id,
      'term' => $this->term,
      'academic_session_id' => $this->academicSessionId,
      'num_of_results' => $resultsToPublishCount,
      'staff_user_id' => $this->staffUser->id,
      'payment_structure' => $this->priceList->payment_structure
    ]);

    //== Update the each $resultsToPublish - Mark as Published
    TermResult::whereIn('id', $this->resultsToPublish->pluck('id'))->update([
      'result_publication_id' => $publication->id
    ]);
    return successRes('Result published successfully');
  }

  static function make(
    User $staffUser,
    Institution $institution,
    SettingsHandler $settingHandler,
    PriceList $priceList,
    array $submittedClassIds
  ): static {
    $className = '';
    switch ($priceList->payment_structure) {
      case PaymentStructure::PerStudentPerTerm:
        $className = PublishResultPerStudentPerTerm::class;
        break;
      case PaymentStructure::PerStudentPerSession:
        $className = PublishResultPerStudentPerSession::class;
        break;
      case PaymentStructure::PerTerm:
        $className = PublishResultPerTerm::class;
        break;
      case PaymentStructure::PerSession:
        $className = PublishResultPerSession::class;
        break;
      default:
        throw new Exception('Payment structure unrecognized');
        break;
    }
    return new $className(
      $staffUser,
      $institution,
      $settingHandler,
      $priceList,
      $submittedClassIds
    );
  }
}
