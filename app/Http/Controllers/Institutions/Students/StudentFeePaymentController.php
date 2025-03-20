<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Actions\Fees\GetStudentFeePaymentSummary;
use App\Core\PaystackHelper;
use App\DTO\PaymentReferenceDto;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Models\Receipt;
use App\Models\ReceiptType;
use App\Models\Student;
use App\Models\User;
use App\Rules\ValidateExistsRule;
use App\Support\MorphMap;
use App\Support\Payments\Merchants\PaymentMerchant;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\FeePaymentUITableFilters;
use App\Support\UITableFilters\ReceiptUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class StudentFeePaymentController extends Controller
{
  function index(Institution $institution, Student $student, Receipt $receipt)
  {
    $receipt->load('receiptType');
    $query = FeePaymentUITableFilters::make(
      request()->all(),
      $receipt
        ->feePayments()
        ->getQuery()
        ->where('user_id', $student->user_id)
    )
      ->filterQuery()
      ->getQuery()
      ->with('academicSession', 'fee')
      ->withCount('feePaymentTracks');
    return inertia('institutions/students/payments/list-student-fee-payments', [
      'receipt' => $receipt,
      'feePayments' => paginateFromRequest($query->latest('id')),
      'student' => $student
    ]);
  }

  function receipts(Institution $institution, Student $student)
  {
    $settingshandler = SettingsHandler::makeFromRoute();

    $query = ReceiptUITableFilters::make(
      request()->all(),
      Receipt::query()->where('user_id', $student->user_id)
    )
      ->filterQuery()
      ->getQuery()
      ->with(
        'academicSession',
        'receiptType',
        'classification',
        'classificationGroup'
      );

    //== Fetch applicable ReceiptTypes
    $receiptTypesQuery = ReceiptType::where(
      'institution_id',
      $student->institutionUser->institution_id
    );
    $term = $settingshandler->getCurrentTerm();
    $academicSessionId = $settingshandler->getCurrentAcademicSession();

    $paymentSummary = (new GetStudentFeePaymentSummary(
      $student,
      $student->classification,
      $term,
      $academicSessionId
    ))->getStudentReceiptPaymentSummary(ReceiptType::all());

    return inertia('institutions/students/payments/list-student-receipts', [
      'fees' => Fee::query()->get(),
      'receiptTypes' => ReceiptType::query()->get(),
      'receipts' => paginateFromRequest($query->latest('id')),
      'student' => $student,
      'classification' => $student->classification,
      'term' => $term,
      'academicSession' => AcademicSession::find($academicSessionId),

      'payableReceiptTypes' => $paymentSummary,
      'instReceiptTypes' => paginateFromRequest(
        $receiptTypesQuery->latest('id')
      )
    ]);
  }

  function showReceiptTypeFee(
    Institution $institution,
    Student $student,
    Classification $classification,
    $term,
    $academicSessionId
  ) {
    $feeSummary = (new GetStudentFeePaymentSummary(
      $student,
      $classification,
      $term,
      $academicSessionId
    ))->getStudentReceiptPaymentSummary(ReceiptType::all())[0];

    return inertia('institutions/students/payments/show-receipt-type-fees', [
      'student' => $student,
      'feeSummary' => $feeSummary,
      'fees' => $feeSummary['fees_to_pay']
    ]);
  }

  function showReceipt(Institution $institution, Receipt $receipt)
  {
    $receipt->load(
      'feePayments.fee',
      'feePayments.feePaymentTracks',
      'classification',
      'classificationGroup',
      'academicSession',
      'receiptType',
      'user'
    );

    return inertia('institutions/students/payments/show-receipt', [
      'receipt' => $receipt,
      'student' => Student::where('user_id', $receipt->user_id)->firstOrFail()
    ]);
  }

  function feePaymentView(Institution $institution, Student $student)
  {
    $student->load('classification');
    return inertia(
      'institutions/students/payments/record-student-fee-payment',
      [
        'student' => $student,
        'fees' => Fee::all(),
        'receiptTypes' => ReceiptType::all(),
        'classifications' => Classification::all(),
        'classificationGroups' => ClassificationGroup::all()
      ]
    );
  }

  function feePaymentStore(
    Request $request,
    Institution $institution,
    Student $student
  ) {
    $data = $request->validate([
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', new Enum(TermType::class)],
      'receipt_type_id' => [
        'required',
        'integer',
        new ValidateExistsRule(ReceiptType::class)
      ],
      'fee_ids' => ['required', 'array', 'min:1'],
      'fee_ids.*' => [
        'required',
        'integer',
        function ($attr, $value, $fail) {
          if (
            !Fee::query()
              ->where('receipt_type_id', request('receipt_type_id'))
              ->where('id', $value)
              ->exists()
          ) {
            $fail('Fee record not found in the selected category');
          }
        },
        new ValidateExistsRule(Fee::class)
      ]
    ]);

    $fees = Fee::whereIn('id', $data['fee_ids'])->get();

    $totalAmount = $fees->sum('amount');
    $user = currentUser();

    $merchant = $request->merchant ?? PaymentMerchantType::Paystack->value;
    $paymentReferenceDto = new PaymentReferenceDto(
      institution_id: $institution->id,
      merchant: $merchant,
      payable: $student->user,
      paymentable: null, // Paying for potentially more that one fee
      amount: $totalAmount,
      purpose: PaymentPurpose::Fee,
      user_id: $user->id,
      reference: PaymentReference::generateReference(),
      redirect_url: instRoute('students.receipts.index', $student->id),
      meta: $data
    );
    [$res, $paymentReference] = PaymentMerchant::make($merchant)->init(
      $paymentReferenceDto
    );
    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
