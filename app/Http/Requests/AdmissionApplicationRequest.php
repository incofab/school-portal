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
      'gender' => ['nullable', new Enum(Gender::class)],
      'dob' => ['nullable', 'string'],
      'religion' => ['nullable', 'string'],
      'lga' => ['nullable', 'string'],
      'state' => ['nullable', 'string'],
      'nationality' => ['nullable', 'string'],
      'intended_class_of_admission' => ['nullable', 'string'],
      'previous_school_attended' => ['nullable', 'string'],
      'fathers_name' => ['nullable', 'string'],
      'fathers_occupation' => ['nullable', 'string'],
      'fathers_phone' => ['nullable', 'string', 'max:20'],
      'fathers_email' => ['nullable', 'string'],
      'fathers_residential_address' => ['nullable', 'string'],
      'fathers_office_address' => ['nullable', 'string'],
      'mothers_name' => ['nullable', 'string'],
      'mothers_occupation' => ['nullable', 'string'],
      'mothers_phone' => ['nullable', 'string', 'max:20'],
      'mothers_email' => ['nullable', 'string'],
      'mothers_residential_address' => ['nullable', 'string'],
      'mothers_office_address' => ['nullable', 'string'],
      'photo' => ['nullable', 'string'],
      'reference' => ['required', 'unique:admission_applications,reference']
      // 'phone' => ['nullable', 'string', 'max:20'],
      // 'guardian_phone' => ['nullable', 'string'],
      // 'email' => ['nullable', 'string'],
      // 'address' => ['nullable', 'string'],
    ];
  }
}