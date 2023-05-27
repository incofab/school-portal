<?php

namespace App\Http\Requests;

use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class DownloadResultRecordingSheetRequest extends FormRequest
{
  public ?AcademicSession $academicSessionObj;
  public ?Course $courseObj;

  protected function prepareForValidation()
  {
    $this->academicSessionObj = AcademicSession::find($this->academicSession);
    $this->courseObj = Course::find($this->course);

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
    $user = $this->user();

    return [
      'academicSession' => ['required'],
      'semester' => ['required', new Enum(Semester::class)],
      'course' => [
        'required',
        function ($attr, $value, $fail) use ($user) {
          if ($user->isAdmin()) {
            return;
          }
          if (
            $user
              ->lecturerCoursesPivot()
              ->where('course_id', $value)
              ->exists()
          ) {
            return;
          }
          $fail('You have not been assigned to this course');
        }
      ]
    ];
  }
}
