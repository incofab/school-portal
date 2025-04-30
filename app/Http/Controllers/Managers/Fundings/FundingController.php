<?php

namespace App\Http\Controllers\Managers\Fundings;

use App\Enums\TransactionType;
use Inertia\Inertia;
use App\Models\Funding;
use App\Enums\WalletType;
use Illuminate\Http\Request;
use App\Models\InstitutionGroup;
use App\Http\Controllers\Controller;
use App\Rules\ValidateFundingReference;
use App\Support\Fundings\FundingHandler;
use App\Support\Fundings\RecordFunding;

class FundingController extends Controller
{
  public function index(Request $request)
  {
    $user = currentUser();

    if (!$user->isAdmin()) {
      abort(400, 'Ünauthorized');
    }

    $fundings = Funding::with('institutionGroup')->latest('id');

    return Inertia::render('managers/fundings/list-fundings', [
      'fundings' => paginateFromRequest($fundings),
      'institutionGroups' => InstitutionGroup::all()
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'institution_group_id' => 'required|exists:institution_groups,id',
      'amount' => 'required|numeric',
      'remark' => 'nullable|string',
      'reference' => ['required', new ValidateFundingReference()]
    ]);

    $institutionGroup = InstitutionGroup::find(
      $validated['institution_group_id']
    );
    $obj = new FundingHandler($institutionGroup, currentUser(), $validated);

    // $type = WalletType::from($validated['type']);
    $type = $request->is_debt ? WalletType::Debt : WalletType::Credit;
    $obj->run($type);

    return $this->ok();
  }

  function recordDebt(Request $request)
  {
    $validated = $request->validate([
      'institution_group_id' => 'required|exists:institution_groups,id',
      'amount' => 'required|numeric',
      'remark' => 'nullable|string',
      'reference' => ['required', new ValidateFundingReference()]
    ]);

    $institutionGroup = InstitutionGroup::find(
      $validated['institution_group_id']
    );

    RecordFunding::make($institutionGroup, currentUser())->recordDebtTopup(
      $validated['amount'],
      $validated['reference'],
      null,
      $validated['remark'] ?? null
    );

    return $this->ok(); 
  }
}
