<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Actions\RecordFeePayment;
use App\Actions\RecordMultiFeePayments;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class MultiFeePaymentController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ]);
  }

  function create()
  {
    return inertia('institutions/payments/record-multi-fee-payment', [
      'fees' => Fee::all(),
      'receiptTypes' => ReceiptType::all(),
      'classifications' => Classification::all(),
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'user_id' => [
        'required',
        Rule::exists('institution_users', 'user_id')
          ->where('institution_id', $institution->id)
          ->whereIn('role', [
            InstitutionUserType::Student,
            InstitutionUserType::Alumni
          ])
      ],
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', new Enum(TermType::class)],
      'method' => ['nullable', 'string'],
      'transaction_reference' => ['nullable', 'string'],
      'fee_ids' => ['required', 'array', 'min:1'],
      'fee_ids.*' => ['required', 'integer', new ValidateExistsRule(Fee::class)]
    ]);

    RecordMultiFeePayments::run($data, $institution);

    return $this->ok();
  }
}
