<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\ReceiptType;
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

  function index()
  {
    $query = ReceiptUITableFilters::make(request()->all(), Receipt::query())
      ->filterQuery()
      ->getQuery();

    $numOfPayments = (clone $query)->count('receipts.id');
    $totalAmountPaid = (clone $query)->sum('receipts.total_amount');

    $query
      ->with(
        // 'user.institutionUser.student',
        'user.student',
        'academicSession',
        'approvedBy',
        'receiptType'
      )
      ->withCount('feePayments');
    // dd(json_encode($query->get(), JSON_PRETTY_PRINT));
    return inertia('institutions/payments/list-receipts', [
      'receiptTypes' => ReceiptType::query()->get(),
      'receipts' => paginateFromRequest($query->latest('id')),
      'num_of_payments' => $numOfPayments,
      'total_amount_paid' => $totalAmountPaid
    ]);
  }
}
