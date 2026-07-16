<?php

namespace App\Http\Controllers\Managers\Billings;

use App\Enums\PriceLists\PaymentStructure;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\InstitutionGroup;
use App\Enums\PriceLists\PriceType;
use App\Http\Controllers\Controller;
use App\Models\PriceList;
use Illuminate\Validation\ValidationException;

class BillingsController extends Controller
{
  public function index()
  {
    $user = currentUser();

    if (!$user->isManager()) {
      abort(400, 'Ünauthorized');
    }

    $billings = PriceList::with('institutionGroup')->latest('id');

    return Inertia::render('managers/billings/list-billings', [
      'billings' => paginateFromRequest($billings),
      'institutionGroups' => InstitutionGroup::all()
    ]);
  }

  public function store(Request $request)
  {
    abort_unless(currentUser()->isAdmin(), 403);

    $validated = $request->validate([
      'institution_group_id' => 'required|exists:institution_groups,id',
      'amount' => 'required|numeric|min:0.01',
      'partner_commission' => 'nullable|numeric|min:0',
      'billable' => [
        'required',
        'string',
        Rule::in(array_map(fn($case) => $case->value, PriceType::cases())) // Ensure the value is one of the PriceType enum values
      ],
      'payment_structure' => [
        'required',
        'string',
        Rule::in(
          array_map(fn($case) => $case->value, PaymentStructure::cases())
        )
      ]
    ]);

    $priceList = PriceList::query()
      ->where('type', $validated['billable'])
      ->where('institution_group_id', $validated['institution_group_id'])
      ->first();

    $partnerCommission = $priceList
      ? $validated['partner_commission'] ?? $priceList->partner_commission
      : $validated['partner_commission'] ?? 0;

    if ($partnerCommission >= $validated['amount']) {
      return throw ValidationException::withMessages([
        'partner_commission' =>
          'The full price must be greater than the partner commission.'
      ]);
    }

    PriceList::updateOrCreate(
      [
        'type' => $validated['billable'],
        'institution_group_id' => $validated['institution_group_id']
      ],
      [
        'payment_structure' => $validated['payment_structure'],
        'amount' => $validated['amount'],
        'partner_commission' => $partnerCommission
      ]
    );

    return $this->ok();
  }
}
