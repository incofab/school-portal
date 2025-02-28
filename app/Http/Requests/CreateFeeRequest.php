<?php

namespace App\Http\Requests;

use App\Enums\PaymentInterval;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\ReceiptType;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateFeeRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request. 
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('fees', 'title')
          ->where('institution_id', currentInstitution()->id)
          ->where('receipt_type_id', $this->receipt_type_id)
          ->ignore($this->fee?->id, 'id')
      ],
      'amount' => ['required', 'numeric', 'min:1'],
      'payment_interval' => ['nullable', new Enum(PaymentInterval::class)],
      'receipt_type_id' => [
        'required',
        new ValidateExistsRule(ReceiptType::class)
      ],
      'classification_group_id' => [
        'nullable',
        new ValidateExistsRule(ClassificationGroup::class)
      ],
      'classification_id' => [
        'nullable',
        new ValidateExistsRule(Classification::class)
      ]
    ];
  }
}
