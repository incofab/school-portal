<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Actions\RecordFeePayment;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Support\UITableFilters\FeePaymentUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class FeePaymentController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except([
      'index',
      'search',
      'show'
    ]);
  }

  function index()
  {
    $query = FeePaymentUITableFilters::make(
      request()->all(),
      FeePayment::query()
    )
      ->filterQuery()
      ->getQuery()
      ->with('user', 'academicSession', 'fee')
      ->withCount('feePaymentTracks');
    return inertia('institutions/payments/list-fee-payments', [
      'fees' => Fee::query()->get(),
      'feePayments' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function create()
  {
    return inertia('institutions/payments/record-fee-payment', [
      'fee' => Fee::query()->get()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'reference' => [
        'required',
        Rule::unique('fee_payment_tracks', 'reference')
      ],
      'fee_id' => [
        'required',
        Rule::exists('fees', 'id')->where('institution_id', $institution->id)
      ],
      'user_id' => [
        'required',
        Rule::exists('institution_users', 'user_id')
          ->where('institution_id', $institution->id)
          ->whereIn('role', [
            InstitutionUserType::Student,
            InstitutionUserType::Alumni
          ])
      ],
      'amount' => ['required', 'numeric', 'min:1'],
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', new Enum(TermType::class)],
      'method' => ['nullable', 'string']
    ]);

    [$feePayment] = RecordFeePayment::run($data, $institution);

    return $this->ok(['feePayment' => $feePayment]);
  }

  function show(
    Request $request,
    Institution $institution,
    FeePayment $feePayment
  ) {
    return inertia('institutions/payments/show-fee-payment', [
      'feePayment' => $feePayment->load(
        'fee',
        'user',
        'academicSession',
        'feePaymentTracks.confirmedBy'
      )
    ]);
  }

  function destroy(Institution $institution, FeePayment $feePayment)
  {
    $feePayment->delete();
    return $this->ok();
  }
}
