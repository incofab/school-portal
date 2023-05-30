<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStaffRequest extends FormRequest
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
      ...User::generalRule($this->institutionUser?->id),
      'role' => [
        'required',
        Rule::notIn([
          InstitutionUserType::Student->value,
          InstitutionUserType::Alumni->value
        ])
      ]
    ];
  }
}
