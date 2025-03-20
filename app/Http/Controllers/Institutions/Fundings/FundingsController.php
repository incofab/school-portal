<?php

namespace App\Http\Controllers\Institutions\Fundings;

use App\Models\Institution;
use App\DTO\PaymentReferenceDto;
use Illuminate\Http\Request;
use App\Models\PaymentReference;
use App\Enums\InstitutionUserType;
use App\Enums\Payments\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Enums\Payments\PaymentPurpose;
use App\Support\Payments\Merchants\PaymentMerchant;

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
    $user = currentUser();
    /*
    $totalAmount = $data['amount'];
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
    */
    $merchant = $request->merchant ?? PaymentMerchantType::Paystack->value;
    $paymentReferenceDto = new PaymentReferenceDto(
      institution_id: $institution->id,
      merchant: $merchant,
      payable: $institution->institutionGroup,
      paymentable: null,
      amount: $data['amount'],
      purpose: PaymentPurpose::WalletFunding,
      user_id: $user->id,
      reference: $request->reference,
      redirect_url: instRoute('fundings.index')
    );
    [$res, $paymentReference] = PaymentMerchant::make($merchant)->init(
      $paymentReferenceDto
    );
    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
