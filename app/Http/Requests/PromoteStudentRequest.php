<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoteStudentRequest extends FormRequest
{
  protected function prepareForValidation()
  {
  }

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
      'institution_id' => ['required'],
      // 'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      // 'classification_group_id' => [
      //   'required',
      //   'exists:classification_groups,id'
      // ],
      'promotions' => ['required', 'array', 'min:1'],
      'promotions.*.destination_classification_id' => [
        'required',
        'exists:classifications,id'
      ],
      'promotions.*.from' => ['required', 'integer', 'min:0', 'max:100'],
      'promotions.*.to' => [
        'required',
        'integer',
        'max:100',
        'gte:promotions.*.from'
      ]
    ];
  }
}
