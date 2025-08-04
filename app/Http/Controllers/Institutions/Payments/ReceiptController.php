<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Support\UITableFilters\ReceiptUITableFilters;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ])->except(['generateUniversalReceipt', 'printUniversalReceipt']);
  }

  function index(Institution $institution)
  {
    $query = ReceiptUITableFilters::make(
      request()->all(),
      Receipt::query()->select('receipts.*')
    )
      ->filterQuery()
      ->getQuery();

    $numOfPayments = (clone $query)->count('receipts.id');
    $totalAmountPaid = (clone $query)->sum('receipts.amount_paid');
    $totalAmountRemaining = (clone $query)->sum('receipts.amount_remaining');

    $query
      ->with('user.student', 'academicSession', 'fee')
      ->withCount('feePayments');

    return inertia('institutions/payments/list-receipts', [
      'receipts' => paginateFromRequest($query->latest('id')),
      'num_of_payments' => $numOfPayments,
      'total_amount_paid' => $totalAmountPaid,
      'total_amount_remaining' => $totalAmountRemaining
    ]);
  }

  function show(Institution $institution, Receipt $receipt)
  {
    return inertia('institutions/payments/show-receipt', [
      'receipt' => $receipt->load(
        'user',
        'academicSession',
        'fee',
        'feePayments.confirmedBy',
        'feePayments.payable'
      )
    ]);
  }

  function printUniversalReceipt(
    Institution $institution,
    User $user,
    Request $request
  ) {
    $receipts = $user
      ->receipts()
      ->where('term', $request->term)
      ->where('academic_session_id', $request->academic_session_id)
      ->with(
        'fee',
        'feePayments.payable',
        'feePayments.confirmedBy',
        'academicSession',
        'user'
      )
      ->get();

    $student = Student::where('user_id', $user->id)
      ->with('classification')
      ->firstOrFail();

    return inertia('institutions/students/payments/print-universal-receipt', [
      'receipts' => $receipts,
      'student' => $student,
      'user' => $user,
      'term' => $request->term,
      'academic_session' => AcademicSession::find($request->academic_session_id)
    ]);

    //= Set a session variable
    // Session::put('receipts', $receipts);
    // Session::put('student', $student);

    // return $this->ok();
  }
}
