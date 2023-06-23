<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class CummulativeResultRequest extends FormRequest
{
  public ?Classification $classificationObj = null;
  public ?AcademicSession $academicSessionObj = null;

  protected function prepareForValidation()
  {
    $institution = currentInstitution();
    if (!$this->academicSession && !$this->classification) {
      return;
    }
    $this->classificationObj = Classification::where(
      'id',
      $this->classification
    )
      ->where('institution_id', $institution->id)
      ->first();
    $this->academicSessionObj = AcademicSession::where(
      'id',
      $this->academicSession
    )->first();

    if (!$this->classificationObj || !$this->academicSessionObj) {
      return throw ValidationException::withMessages([
        'classification' => 'Select a class and an academic session',
        'academicSession' => 'Select a class and an academic session'
      ]);
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
      'classification' => ['nullable', 'integer'],
      'academicSession' => ['nullable', 'integer'],
      'term' => ['nullable', new Enum(TermType::class)]
    ];
  }
}
