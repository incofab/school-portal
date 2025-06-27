<?php

namespace App\Http\Controllers\Institutions\Payments;

use App\Actions\Fees\GetClassFeePaymentSummary;
use App\Actions\Payments\FeePaymentHandler;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Rules\ValidateExistsRule;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\FeePaymentUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class FeePaymentController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ])->except(['index', 'search', 'show']);
  }

  function index(Institution $institution, ?Fee $fee = null)
  {
    $query = FeePaymentUITableFilters::make(
      [...request()->all(), ...$fee ? ['fee' => $fee->id] : []],
      FeePayment::query()->select('fee_payments.*')
    )
      ->filterQuery()
      ->getQuery();

    $numOfPayments = (clone $query)->count('fee_payments.id');
    $totalAmountPaid = (clone $query)->sum('fee_payments.amount');
    $query->with('receipt.academicSession', 'receipt.user', 'fee');
    return inertia('institutions/payments/list-fee-payments', [
      'fees' => Fee::query()->get(),
      'feePayments' => paginateFromRequest($query->latest('id')),
      'num_of_payments' => $numOfPayments,
      'total_amount_paid' => $totalAmountPaid
    ]);
  }

  function create(Institution $institution)
  {
    return inertia('institutions/payments/record-fee-payment', [
      'fee' => Fee::query()->get()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $feeValidation = new ValidateExistsRule(Fee::class);
    $data = $request->validate([
      'reference' => ['required', Rule::unique('fee_payments', 'reference')],
      'fee_id' => ['required', $feeValidation],
      'user_id' => [
        'required',
        Rule::exists('institution_users', 'user_id')
          ->where('institution_id', $institution->id)
          ->whereIn('role', [
            InstitutionUserType::Student,
            InstitutionUserType::Alumni
          ])
      ],
      'amount' => ['required', 'numeric', 'min:1'],
      'method' => ['nullable', 'string']
    ]);

    [$receipt, $feePayment] = FeePaymentHandler::make($institution)->create(
      $data,
      $feeValidation->getModel(),
      null,
      currentUser(),
      allowOverPayment: false
    );

    return $this->ok(['feePayment' => $feePayment]);
  }

  /** @deprecated No longer in use */
  function show(
    Request $request,
    Institution $institution,
    FeePayment $feePayment
  ) {
    return inertia('institutions/payments/show-fee-payment', [
      'feePayment' => $feePayment->load(
        'fee',
        'receipt.user',
        'receipt.academicSession'
      )
    ]);
  }

  // Todo: write a test for this function
  function feePaymentSummary(Institution $institution, Request $request)
  {
    $classRule = new ValidateExistsRule(Classification::class);
    $data = $request->validate([
      'classification_id' => ['required', $classRule],
      'term' => ['nullable', new Enum(TermType::class)],
      'academic_session_id' => ['nullable', 'integer'],
      'download' => ['nullable', 'boolean']
    ]);

    $classification = $classRule->getModel();
    $settingsHandler = SettingsHandler::makeFromRoute();
    $term = $data['term'] ?? $settingsHandler->getCurrentTerm();
    $academicSession = AcademicSession::findOrFail(
      $data['academic_session_id'] ??
        $settingsHandler->getCurrentAcademicSession()
    );

    [$feePaymentSummaries, $fees] = (new GetClassFeePaymentSummary(
      $classification,
      $term,
      $academicSession->id
    ))->run();

    if ($request->download) {
      return GetClassFeePaymentSummary::downloadAsExcel(
        $fees,
        $feePaymentSummaries
      );
    }

    // dd(json_encode($feePaymentSummaries, JSON_PRETTY_PRINT));
    return inertia('institutions/payments/fee-payment-summary', [
      'fees' => $fees,
      'feePaymentSummaries' => $feePaymentSummaries,
      'term' => $term,
      'academicSession' => $academicSession,
      'classification' => $classification
    ]);
  }

  function destroy(Institution $institution, FeePayment $feePayment)
  {
    $feePayment->load('fee', 'receipt');
    FeePaymentHandler::make($institution)->delete($feePayment);
    return $this->ok();
  }
}
