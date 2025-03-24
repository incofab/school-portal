<?php

namespace App\Http\Controllers\Institutions\Fundings;

use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;

class TransactionController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Institution $institution, ?string $walletType = null)
  {
    $query = Transaction::query()
      ->where('institution_group_id', $institution->institution_group_id)
      ->when($walletType, fn($q) => $q->where('wallet', $walletType))
      ->latest('id');

    return inertia('institutions/fundings/list-transactions', [
      'transactions' => paginateFromRequest($query),
      'wallet' => $walletType
    ]);
  }
}
