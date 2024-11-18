<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class DownloadResultRecordingSheetRequest extends FormRequest
{
  public ?AcademicSession $academicSessionObj;
  public ?Classification $classificationObj;

  protected function prepareForValidation()
  {
    $this->academicSessionObj = AcademicSession::find($this->academicSession);
    $this->classificationObj = Classification::find($this->classification);

    if (!$this->academicSessionObj) {
      throw ValidationException::withMessages([
        'academicSession' => 'Academic session not selected/invalid'
      ]);
    }

    if (!$this->classificationObj) {
      throw ValidationException::withMessages([
        'classification' => 'Class not selected/invalid'
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
      'academicSession' => ['required'],
      'classification' => ['required'],
      'term' => ['required', new Enum(TermType::class)],
      'forMidTerm' => ['required', 'boolean']
    ];
  }
}
