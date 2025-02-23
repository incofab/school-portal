<?php

namespace App\Http\Controllers\Institutions\Fundings;

use App\Models\Institution;
use App\Core\PaystackHelper;
use Illuminate\Http\Request;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Enums\Payments\PaymentPurpose;
use App\Support\MorphMap;

class FundingsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  //
  public function index(Institution $institution)
  {
    $fundings = $institution->institutionGroup->fundings();

    return inertia('institutions/fundings/list-fundings', [
      'fundings' => paginateFromRequest($fundings)
    ]);
  }

  function create()
  {
    return inertia('institutions/fundings/create-funding');
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'amount' => 'required|numeric|min:1',
      'reference' => [
        'required',
        'string',
        'unique:payment_references,reference',
        'unique:fundings,reference'
      ]
    ]);

    $totalAmount = $data['amount'];
    $user = currentUser();
    $reference = $data['reference']; //Str::orderedUuid();
    $purpose = PaymentPurpose::WalletFunding->value;

    PaymentReference::query()->create([
      'institution_id' => $institution->id,
      'user_id' => $user->id,
      'payable_id' => $institution->institution_group_id, //$institution->institutionGroup->id
      'payable_type' => MorphMap::key(InstitutionGroup::class),
      'amount' => $totalAmount,
      'purpose' => $purpose,
      'reference' => $reference,
      'redirect_url' => instRoute('fundings.index')
    ]);

    $res = PaystackHelper::make()->initialize(
      $totalAmount,
      $user->email,
      route('paystack.callback'),
      $reference,
      $purpose
    );
    return $this->ok($res->toArray());
  }
}
