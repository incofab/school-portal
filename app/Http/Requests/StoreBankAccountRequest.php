<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Update this if you want to authorize based on user permissions
  }

  public function rules(): array
  {
    return [
      'bank_name' => ['required', 'string', 'max:255'],
      'bank_code' => ['required', 'string', 'max:255'],
      'account_name' => ['required', 'string', 'max:255'],
      'account_number' => ['required', 'string', 'max:30'],
      // 'institution_id' => ['nullable', 'integer'],
      'is_primary' => ['nullable', 'boolean']
    ];
  }
}
