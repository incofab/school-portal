<?php

namespace App\Http\Requests;

use App\Enums\Semester;
use App\Enums\InstitutionUserType;
use App\Models\Hostel;
use App\Rules\FailIfRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class StoreHostelUserRequest extends FormRequest
{
  public ?Hostel $hostel;

  protected function prepareForValidation()
  {
    $hostel = Hostel::find($this->hostel_id);

    if (!$hostel) {
      return throw ValidationException::withMessages([
        'hostel_id' => 'This hostel does not exist'
      ]);
    }

    $this->hostel = $hostel;
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
      'hostel_id' => [
        'required'
        // new FailIfRule(
        //   $this->user
        //     ->hostels()
        //     ->where('active', true)
        //     ->exists(),
        //   'User is already assigned to a hostel'
        // )
      ],
      'user_ids' => ['required', 'array', 'min:1'],
      'user_ids.*' => [
        'required',
        Rule::exists('users', 'id')->where(
          'role',
          InstitutionUserType::Student->value
        )
      ],
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'semester' => ['nullable', new Enum(Semester::class)]
    ];
  }
  protected function passedValidation()
  {
    $usersCount = count($this->user_ids);
    if ($this->hostel->num_of_users + $usersCount >= $this->hostel->capacity) {
      return throw ValidationException::withMessages([
        'hostel_id' =>
          'This hostel does not have to capacity to contain these users'
      ]);
    }
  }
}
