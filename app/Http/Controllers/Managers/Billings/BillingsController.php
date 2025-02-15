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

class BillingsController extends Controller
{
    public function index()
    {
        $user = currentUser();

        if (!$user->isManager()) {
            abort(400, "Ünauthorized");
        }

        $billings = PriceList::with('institutionGroup')->latest('id');

        return Inertia::render(
            'managers/billings/list-billings',
            [
                'billings' => paginateFromRequest($billings),
                'institutionGroups' => InstitutionGroup::all()
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_group_id' => 'required|exists:institution_groups,id',
            'amount' => 'required|numeric',
            'billable' => [
                'required',
                'string',
                Rule::in(array_map(fn($case) => $case->value, PriceType::cases())), // Ensure the value is one of the PriceType enum values
            ],
            'payment_structure' => [
                'required',
                'string',
                Rule::in(array_map(fn($case) => $case->value, PaymentStructure::cases())),
            ],
        ]);

        PriceList::updateOrCreate(
            [
                'type' => $validated['billable'],
                'institution_group_id' => $validated['institution_group_id']
            ],
            [
                'payment_structure' => $validated['payment_structure'],
                'amount' => $validated['amount']
            ]
        );

        return $this->ok();
    }
}