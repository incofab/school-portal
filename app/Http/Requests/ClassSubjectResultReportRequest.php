<?php

namespace App\Http\Requests;

use App\Models\AcademicSession;
use App\Models\Classification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ClassSubjectResultReportRequest extends FormRequest
{
  public ?Classification $classificationObj = null;
  public ?AcademicSession $academicSessionObj = null;

  protected function prepareForValidation()
  {
    if (!$this->academicSession || !$this->classification) {
      return;
    }

    $this->classificationObj = Classification::query()
      ->where('id', $this->classification)
      ->first();
    $this->academicSessionObj = AcademicSession::query()
      ->where('id', $this->academicSession)
      ->first();

    if (!$this->classificationObj || !$this->academicSessionObj) {
      throw ValidationException::withMessages([
        'classification' => 'Select a class and an academic session',
        'academicSession' => 'Select a class and an academic session'
      ]);
    }
  }

  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'classification' => ['nullable', 'integer'],
      'academicSession' => ['nullable', 'integer']
    ];
  }
}
