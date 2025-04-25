<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Receipt;
use App\Support\UITableFilters\ReceiptUITableFilters;

class ReceiptController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ]);
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
}
