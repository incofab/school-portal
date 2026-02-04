<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInternalNotificationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'title' => ['required', 'string', 'max:255'],
      'body' => ['nullable', 'string'],
      'action_url' => ['nullable', 'string', 'max:255'],
      'type' => ['nullable', 'string', 'max:100'],
      'data' => ['nullable', 'array'],
      'targets' => ['required', 'array', 'min:1'],
      'targets.*.type' => ['required', 'string'],
      'targets.*.id' => ['required', 'integer']
    ];
  }
}
