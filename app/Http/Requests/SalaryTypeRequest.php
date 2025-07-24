<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SalaryTypeRequest extends FormRequest
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
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'title' => ['required', 'string', 'max:255', 'min:3'],
      'type' => ['required', new Enum(TransactionType::class)],
      'parent_id' => ['nullable', 'exists:salary_types,id'],
      'percentage' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
      'description' => ['nullable', 'string', 'max:500']
    ];
  }

  /**
   * Get the error messages for the defined validation rules.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'title.required' => 'The title is required.',
      'title.string' => 'The title must be a string.',
      'title.max' => 'The title may not be greater than 255 characters.',
      'title.min' => 'The title must be at least 3 characters.',

      'type.required' => 'The transaction type is required.',
      'type.enum' => 'The selected transaction type is invalid.',

      'parent_id.exists' => 'The selected parent ID does not exist.',

      'percentage.numeric' => 'The percentage must be a number.',
      'percentage.min' => 'The percentage must be at least 0.5.',
      'percentage.max' => 'The percentage may not be greater than 100.',

      'description.string' => 'The description must be a string.',
      'description.max' =>
        'The description may not be greater than 500 characters.'
    ];
  }

  /**
   * Get custom attributes for validator errors.
   *
   * @return array<string, string>
   */
  public function attributes(): array
  {
    return [
      'title' => 'title',
      'type' => 'transaction type',
      'parent_id' => 'parent salary type',
      'percentage' => 'percentage',
      'description' => 'description'
    ];
  }
}
