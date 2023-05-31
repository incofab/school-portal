<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class DownloadCourseResultSheetRequest extends FormRequest
{
  public ?AcademicSession $academicSessionObj;
  public ?Course $courseObj;
  public ?Classification $classificationObj;

  protected function prepareForValidation()
  {
    $this->academicSessionObj = AcademicSession::find($this->academicSession);
    $this->courseObj = Course::find($this->course);
    $this->classificationObj = Classification::find($this->classification);

    if (!$this->academicSessionObj) {
      return throw ValidationException::withMessages([
        'academicSession' => 'Academic session not selected/invalid'
      ]);
    }

    if (!$this->courseObj) {
      return throw ValidationException::withMessages([
        'course' => 'Course not selected/invalid'
      ]);
    }

    if (!$this->classificationObj) {
      return throw ValidationException::withMessages([
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
      'course' => ['required']
    ];
  }
}
