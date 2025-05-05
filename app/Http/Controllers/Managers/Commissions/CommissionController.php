<?php

namespace App\Http\Controllers\Managers\Commissions;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Inertia\Inertia;

class CommissionController extends Controller
{
  //
  public function index()
  {
    $user = currentUser();

    if ($user->isAdmin()) {
      $query = Commission::with([
        'partner.user',
        'institutionGroup',
        'commissionable'
      ]);
    } else {
      $query = $user->partner
        ->commissions()
        ->with(['institutionGroup', 'commissionable']);
    }

    return Inertia::render('managers/commissions/list-commissions', [
      'commissions' => paginateFromRequest($query->orderBy('id', 'desc'))
    ]);
  }
}
