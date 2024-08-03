<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Core\PaystackHelper;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
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
use App\Support\SettingsHandler;
use App\Support\UITableFilters\FeePaymentUITableFilters;
use App\Support\UITableFilters\ReceiptUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class StudentFeePaymentController extends Controller
{
  function index(Institution $institution, User $user, Receipt $receipt)
  {
    $receipt->load('receiptType');
    $query = FeePaymentUITableFilters::make(
      request()->all(),
      $receipt
        ->feePayments()
        ->getQuery()
        ->where('user_id', $user->id)
    )
      ->filterQuery()
      ->getQuery()
      ->with('academicSession', 'fee')
      ->withCount('feePaymentTracks');
    return inertia('institutions/students/payments/list-student-fee-payments', [
      'receipt' => $receipt,
      'feePayments' => paginateFromRequest($query->latest('id')),
      'student' => $user->institutionStudent()
    ]);
  }

  function receipts(Institution $institution, User $user)
  {
    $query = ReceiptUITableFilters::make(
      request()->all(),
      Receipt::query()->where('user_id', $user->id)
    )
      ->filterQuery()
      ->getQuery()
      ->with(
        'academicSession',
        'receiptType',
        'classification',
        'classificationGroup'
      );

    return inertia('institutions/students/payments/list-student-receipts', [
      'fees' => Fee::query()->get(),
      'receiptTypes' => ReceiptType::query()->get(),
      'receipts' => paginateFromRequest($query->latest('id')),
      'student' => $user->institutionStudent()
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
    $reference = Str::orderedUuid();
    PaymentReference::query()->create([
      'institution_id' => $institution->id,
      'user_id' => $user->id,
      'payable_id' => $student->user_id,
      'payable_type' => 'user',
      'amount' => $totalAmount,
      'purpose' => PaymentPurpose::Fee->value,
      'meta' => $data,
      'reference' => $reference,
      'redirect_url' => instRoute('users.receipts.index', $user->id)
    ]);

    $res = (new PaystackHelper($institution))->initialize(
      $totalAmount,
      $user->email,
      route('paystack.callback'),
      $reference
    );
    info(['ok' => true, ...$res->toArray()]);
    return $this->ok($res->toArray());
  }
}
