<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AdmissionApplicationRequest extends FormRequest
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
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'other_names' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:20'],
      'gender' => ['nullable', new Enum(Gender::class)],
      'fathers_name' => ['nullable', 'string'],
      'mothers_name' => ['nullable', 'string'],
      'fathers_occupation' => ['nullable', 'string'],
      'mothers_occupation' => ['nullable', 'string'],
      'guardian_phone' => ['nullable', 'string'],
      'photo' => ['nullable', 'string'],
      'email' => ['nullable', 'string'],
      'address' => ['nullable', 'string'],
      'previous_school_attended' => ['nullable', 'string'],
      'dob' => ['nullable', 'string'],
      'religion' => ['nullable', 'string'],
      'nationality' => ['nullable', 'string'],
      'reference' => ['required', 'unique:admission_applications,reference']
    ];
  }
}
