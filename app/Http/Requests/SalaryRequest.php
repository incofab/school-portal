<?php

namespace App\Http\Requests;

use App\Models\SalaryType;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class SalaryRequest extends FormRequest
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
      'salary_type_id' => [
        'required',
        new ValidateExistsRule(SalaryType::class)
      ],
      'description' => 'nullable|string|max:255',
      'amount' => 'nullable|numeric|min:0',
      ...$this->salary
        ? []
        : [
          'institution_user_id' =>
            'required|integer|exists:institution_users,id'
        ]
    ];
  }

  public function messages(): array
  {
    return [
      'salary_type_id.required' => 'Please select a salary type.',
      'salary_type_id.integer' => 'The salary type ID must be a valid number.',
      'salary_type_id.exists' => 'The selected salary type does not exist.',

      'description.string' => 'The description must be a string.',
      'description.max' =>
        'The description may not be greater than 255 characters.',

      'amount.required' => 'The salary amount is required.',
      'amount.numeric' => 'The amount must be a valid number.',
      'amount.min' => 'The amount must be at least 0.',

      'institution_user_id.required' => 'Please select a staff member.',
      'institution_user_id.integer' => 'The staff ID must be a valid number.',
      'institution_user_id.exists' =>
        'The selected staff member does not exist.'
    ];
  }

  public function attributes(): array
  {
    return [
      'salary_type_id' => 'salary type',
      'description' => 'description',
      'amount' => 'salary amount',
      'institution_user_id' => 'staff member'
    ];
  }
}
