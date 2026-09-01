<?php

namespace App\Http\Controllers;

use App\Actions\Payments\FindPublicStudent;
use App\Actions\Payments\GetPublicStudentFeePaymentData;
use App\Actions\Payments\InitializePublicStudentFeePayment;
use App\Enums\Payments\PaymentMerchantType;
use App\Models\Fee;
use App\Models\Institution;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class PublicStudentFeePaymentController extends Controller
{
  public function show(Institution $institution, string $studentCode)
  {
    $student = FindPublicStudent::make($institution, $studentCode)->run();

    return inertia(
      'public/student-fee-payment',
      GetPublicStudentFeePaymentData::make($institution, $student)->run()
    );
  }

  public function store(
    Request $request,
    Institution $institution,
    string $studentCode
  ) {
    $student = FindPublicStudent::make($institution, $studentCode)->run();
    $feeRule = new ValidateExistsRule(Fee::class);
    $data = $request->validate([
      'fee_id' => [
        'required',
        $feeRule,
        Rule::exists('fees', 'id')->where(
          fn($query) => $query->where('institution_id', $institution->id)
        )
      ],
      'amount' => ['required', 'numeric', 'min:1'],
      'merchant' => ['nullable', new Enum(PaymentMerchantType::class)]
    ]);

    return $this->ok(
      InitializePublicStudentFeePayment::make(
        $institution,
        $student,
        $data
      )->run()
    );
  }
}
