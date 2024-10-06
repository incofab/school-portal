<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Actions\Payments\InsertFeePaymentFromSheet;
use App\Actions\Payments\PrepareFeePaymentRecordingSheet;
use App\Actions\Payments\RecordFeePayment;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Rules\ExcelRule;
use App\Rules\ValidateExistsRule;
use App\Support\UITableFilters\FeePaymentUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Storage;

class FeePaymentController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ])->except(['index', 'search', 'show']);
  }

  function index()
  {
    $query = FeePaymentUITableFilters::make(
      request()->all(),
      FeePayment::query()
    )
      ->filterQuery()
      ->getQuery();

    $numOfPayments = (clone $query)->count('fee_payments.id');
    $totalAmountPaid = (clone $query)->sum('fee_payments.amount_paid');
    $pendingAmount = (clone $query)->sum('fee_payments.amount_remaining');
    $query
      ->with('user', 'academicSession', 'fee')
      ->withCount('feePaymentTracks');
    return inertia('institutions/payments/list-fee-payments', [
      'fees' => Fee::query()->get(),
      'receiptTypes' => ReceiptType::query()->get(),
      'feePayments' => paginateFromRequest($query->latest('id')),
      'num_of_payments' => $numOfPayments,
      'total_amount_paid' => $totalAmountPaid,
      'pending_amount' => $pendingAmount
    ]);
  }

  function create()
  {
    return inertia('institutions/payments/record-fee-payment', [
      'fee' => Fee::query()->get()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'reference' => [
        'required',
        Rule::unique('fee_payment_tracks', 'reference')
      ],
      'fee_id' => [
        'required',
        Rule::exists('fees', 'id')->where('institution_id', $institution->id)
      ],
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
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', new Enum(TermType::class)],
      'method' => ['nullable', 'string'],
      'transaction_reference' => ['nullable', 'string']
    ]);

    [$feePayment] = RecordFeePayment::run($data, $institution);

    return $this->ok(['feePayment' => $feePayment]);
  }

  function show(
    Request $request,
    Institution $institution,
    FeePayment $feePayment
  ) {
    return inertia('institutions/payments/show-fee-payment', [
      'feePayment' => $feePayment->load(
        'fee',
        'user',
        'academicSession',
        'feePaymentTracks.confirmedBy'
      )
    ]);
  }

  public function download(
    Request $request,
    Institution $institution,
    Classification $classification,
    ReceiptType $receiptType
  ) {
    $excelWriter = (new PrepareFeePaymentRecordingSheet(
      $classification,
      $receiptType
    ))->generateSheet();

    $filename = "{$receiptType->title}-{$classification->title}-payment-sheet.xlsx";

    $filename = str_replace(['/', ' '], ['_', '-'], $filename);

    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename);
  }

  public function upload(Request $request, Institution $institution)
  {
    $academicSessionExistsRule = new ValidateExistsRule(AcademicSession::class);
    $receiptTypeExistsRule = new ValidateExistsRule(ReceiptType::class);
    $data = $request->validate([
      'file' => ['required', 'file', new ExcelRule($request->file('file'))],
      'term' => ['nullable', new Enum(TermType::class)],
      'academic_session_id' => ['nullable', $academicSessionExistsRule],
      'receipt_type_id' => ['nullable', $receiptTypeExistsRule]
    ]);

    (new InsertFeePaymentFromSheet(
      $institution,
      $receiptTypeExistsRule->getModel(),
      $academicSessionExistsRule->getModel(),
      $request->term
    ))->upload($request->file);

    return $this->ok();
  }

  function destroy(Institution $institution, FeePayment $feePayment)
  {
    $receipt = $feePayment->receipt;
    $feePayment->delete();
    RecordFeePayment::updateReceiptRecords($receipt);
    return $this->ok();
  }
}
