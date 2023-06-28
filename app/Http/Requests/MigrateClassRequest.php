<?php

namespace App\Http\Requests;

use App\Models\Classification;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Str;

class MigrateClassRequest extends FormRequest
{
  public ?Classification $classification = null;

  protected function prepareForValidation()
  {
    $institution = currentInstitution();

    if (!$this->email) {
      $this->merge(['email' => Str::orderedUuid() . '@email.com']);
    }

    // Class is only considered when we are creating a student not editing
    if (empty($this->student)) {
      $classification = Classification::where('id', $this->classification_id)
        ->where('institution_id', $institution->id)
        ->first();

      if (!$classification) {
        return throw ValidationException::withMessages([
          'classification_id' => 'This class does not exists'
        ]);
      }
      $this->classification = $classification;
    }
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
      'destination_class' => [
        'integer',
        Rule::requiredIf(!$this->move_to_alumni)
      ],
      'move_to_alumni' => ['nullable', 'boolean']
    ];
  }
}
