<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReceiptTypeRequest extends FormRequest
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
        Rule::unique('receipt_types', 'title')
          ->where('institution_id', currentInstitution()->id)
          ->ignore($this->receiptType?->id, 'id')
      ],
      'descriptions' => ['nullable', 'string']
    ];
  }
}
