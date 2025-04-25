<?php

namespace App\Http\Requests;

use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Rules\ValidateMorphRule;
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
          ->ignore($this->fee?->id, 'id')
      ],
      'amount' => ['required', 'numeric', 'min:1'],
      'payment_interval' => ['nullable', new Enum(PaymentInterval::class)],
      'academic_session_id' => [
        Rule::requiredIf(empty($this->fee)),
        'exists:academic_sessions,id'
      ],
      'term' => ['nullable', new Enum(TermType::class)],
      'fee_items' => ['nullable', 'array'],
      'fee_items.*.amount' => ['required', 'numeric', 'min:1'],
      'fee_items.*.title' => ['required', 'string', 'max:255'],
      'fee_categories' => ['required', 'array', 'min:1'],
      'fee_categories.*.feeable_id' => ['required', 'integer'],
      'fee_categories.*.feeable_type' => [
        'required',
        new ValidateMorphRule('feeable')
      ]
    ];
  }
}
