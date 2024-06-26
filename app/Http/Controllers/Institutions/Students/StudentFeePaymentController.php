<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\ReceiptType;
use App\Models\Student;
use App\Models\User;
use App\Support\UITableFilters\FeePaymentUITableFilters;
use App\Support\UITableFilters\ReceiptUITableFilters;

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
      'feePayments' => paginateFromRequest($query->latest('id'))
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
      'receipts' => paginateFromRequest($query->latest('id'))
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
}
