<?php

namespace App\Http\Controllers\Institutions\Students;

use App\DTO\PaymentReferenceDto;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Models\Receipt;
use App\Models\Student;
use App\Rules\ValidateExistsRule;
use App\Support\Payments\Merchants\PaymentMerchant;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\FeePaymentUITableFilters;
use App\Support\UITableFilters\ReceiptUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class StudentFeePaymentController extends Controller
{
  function index(
    Institution $institution,
    Student $student,
    ?Receipt $receipt = null
  ) {
    $receipt?->load('academicSession', 'fee');
    $query = FeePaymentUITableFilters::make(
      [
        ...request()->all(),
        'user' => $student->user_id,
        'receipt' => $receipt?->id
      ],
      FeePayment::query()->select('fee_payments.*')
    )
      ->filterQuery()
      ->getQuery()
      ->with('fee', 'receipt');
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
      Receipt::query()
        ->select('receipts.*')
        ->where('receipts.user_id', $student->user_id)
    )
      ->filterQuery()
      ->getQuery()
      ->with('academicSession', 'fee.feeCategories.feeable');

    return inertia('institutions/students/payments/list-student-receipts', [
      'fees' => Fee::query()->get(),
      'receipts' => paginateFromRequest($query->latest('id')),
      'student' => $student->load('user')
    ]);
  }

  function printReceipt(Institution $institution, Receipt $receipt)
  {
    $receipt->load(
      'fee',
      'feePayments.payable',
      'feePayments.confirmedBy',
      'academicSession',
      'user'
    );

    return inertia('institutions/students/payments/print-receipt', [
      'receipt' => $receipt,
      'student' => Student::where('user_id', $receipt->user_id)
        ->with('classification')
        ->firstOrFail()
    ]);
  }

  function feePaymentView(Institution $institution, Student $student)
  {
    $student->load('classification');

    return inertia(
      'institutions/students/payments/record-student-fee-payment',
      [
        'student' => $student,
        'fees' => $student->studentFees()
      ]
    );
  }

  function feePaymentStore(
    Request $request,
    Institution $institution,
    Student $student
  ) {
    $feeRule = new ValidateExistsRule(Fee::class);
    $data = $request->validate([
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', new Enum(TermType::class)],
      'fee_id' => ['required', $feeRule],
      'amount' => ['nullable', 'numeric']
    ]);

    $fee = $feeRule->getModel();
    $amount = $data['amount'] ?? $fee->amount;
    $user = currentUser();
    $settingshandler = SettingsHandler::makeFromRoute();

    $merchant = $request->merchant ?? PaymentMerchantType::Paystack->value;
    $paymentReferenceDto = new PaymentReferenceDto(
      institution_id: $institution->id,
      merchant: $merchant,
      payable: $student->user,
      paymentable: $fee,
      amount: $amount,
      purpose: PaymentPurpose::Fee,
      user_id: $user->id,
      reference: PaymentReference::generateReference(),
      redirect_url: instRoute('students.receipts.index', $student->id),
      meta: [
        ...$data,
        'academic_session_id' =>
          $data['academic_session_id'] ??
          $settingshandler->getCurrentAcademicSession(),
        'term' => $data['term'] ?? $settingshandler->getCurrentTerm()
      ]
    );
    [$res, $paymentReference] = PaymentMerchant::make($merchant)->init(
      $paymentReferenceDto
    );
    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
