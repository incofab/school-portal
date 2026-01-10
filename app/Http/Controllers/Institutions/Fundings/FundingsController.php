<?php

namespace App\Http\Controllers\Institutions\Fundings;

use App\Models\Institution;
use App\DTO\PaymentReferenceDto;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Enums\Payments\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Enums\Payments\PaymentPurpose;
use App\Models\Funding;
use App\Support\Payments\Merchants\PaymentMerchant;
use Illuminate\Validation\Rules\Enum;

class FundingsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Institution $institution, ?string $walletType = null)
  {
    $query = Funding::query()
      ->where('institution_group_id', $institution->institution_group_id)
      ->when($walletType, fn($q) => $q->where('wallet', $walletType))
      ->with('transaction')
      ->latest('id');

    return inertia('institutions/fundings/list-fundings', [
      'fundings' => paginateFromRequest($query),
      'wallet' => $walletType
    ]);
  }

  function create(Institution $institution)
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
      ],
      'merchant' => ['nullable', new Enum(PaymentMerchantType::class)]
    ]);
    $user = currentUser();

    $merchant = $request->merchant ?? PaymentMerchantType::Monnify->value;
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
