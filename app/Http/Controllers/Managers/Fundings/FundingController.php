<?php

namespace App\Http\Controllers\Managers\Fundings;

use Barryvdh\DomPDF\Facade\Pdf;
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

    $fundings = Funding::with('institutionGroup', 'transaction')->latest('id');

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

  public function receipt(Funding $funding)
  {
    $user = currentUser();

    if (!$user->isAdmin()) {
      abort(400, 'Ünauthorized');
    }

    $funding->load('institutionGroup');

    $data = [
      'receipt_number' => sprintf('RCPT-%06d', $funding->id),
      'receipt_date' => $funding->created_at?->toDayDateTimeString(),
      'funding' => $funding,
      'institution_group' => $funding->institutionGroup,
      'processed_by' => $user
    ];

    $pdf = Pdf::loadView('receipts.funding-receipt', $data);

    return $pdf->download('funding-receipt.pdf');
  }
}
