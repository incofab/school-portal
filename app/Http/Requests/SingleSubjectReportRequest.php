<?php

namespace App\Http\Requests;

use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SingleSubjectReportRequest extends FormRequest
{
  public ?Classification $classificationObj = null;
  public ?AcademicSession $academicSessionObj = null;
  public ?Course $courseObj = null;

  protected function prepareForValidation()
  {
    if (!$this->academicSession || !$this->classification || !$this->course) {
      return;
    }

    $this->classificationObj = Classification::query()
      ->where('id', $this->classification)
      ->first();
    $this->academicSessionObj = AcademicSession::query()
      ->where('id', $this->academicSession)
      ->first();
    $this->courseObj = Course::query()
      ->where('id', $this->course)
      ->first();

    if (
      !$this->classificationObj ||
      !$this->academicSessionObj ||
      !$this->courseObj
    ) {
      throw ValidationException::withMessages([
        'classification' => 'Select a class, subject, and academic session',
        'academicSession' => 'Select a class, subject, and academic session',
        'course' => 'Select a class, subject, and academic session'
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
      'academicSession' => ['nullable', 'integer'],
      'course' => ['nullable', 'integer']
    ];
  }
}
