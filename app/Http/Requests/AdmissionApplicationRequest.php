<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
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
      'reference' => ['required', 'unique:admission_applications,reference'],
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'other_names' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:20'],
      'email' => ['nullable', 'string'],
      'gender' => ['nullable', new Enum(Gender::class)],
      'nationality' => ['nullable', 'string'],
      'religion' => ['nullable', 'string'],
      'lga' => ['nullable', 'string'],
      'state' => ['nullable', 'string'],
      'intended_class_of_admission' => ['nullable', 'string'],
      'previous_school_attended' => ['nullable', 'string'],
      'dob' => ['nullable', 'string'],
      'address' => ['nullable', 'string'],
      'photo' => ['nullable', 'image', 'mimes:jpg,png,jpeg', 'max:1024'],
      'guardians' => ['required', 'array'],
      'guardians.*.first_name' => ['required', 'string', 'max:255'],
      'guardians.*.last_name' => ['required', 'string', 'max:255'],
      'guardians.*.other_names' => ['nullable', 'string', 'max:255'],
      'guardians.*.phone' => ['required', 'string', 'max:20'],
      'guardians.*.email' => ['nullable', 'string'],
      'guardians.*.relationship' => ['required', new Enum(GuardianRelationship::class)],
    ];
  }
}
