<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\Course;
use App\Models\Institution;
use App\Rules\InstitutionStudentRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class RecordCourseResultRequest extends FormRequest
{
  public Institution $institution;

  protected function prepareForValidation()
  {
    $this->institution = currentInstitution();
    $courseTeacher = $this->courseTeacher;
    // checks if this courseTeacher's course belongs to the current institution
    if (
      !Course::query()
        ->where('id', $courseTeacher->course_id)
        ->exists()
    ) {
      return throw ValidationException::withMessages([
        'result' => 'Access denied'
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
      // 'teacher_user_id' => [
      //   'required',
      //   new InstitutionUserRule($this->institution, UserRoleType::Teacher)
      // ],
      // 'course_id' => ['required', Rule::exists('courses', 'id')],
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'term' => ['required', new Enum(TermType::class)],
      'classification_id' => [
        'required',
        Rule::exists('classifications', 'id')
      ],
      'result' => ['array', 'min:1', Rule::requiredIf(!$this->has('results'))],
      ...$this->resultRule('result.'),
      'results' => ['array', 'min:1', Rule::requiredIf(!$this->has('result'))],
      ...$this->resultRule('results.*.')
    ];
  }

  private function resultRule(string $prefix)
  {
    return [
      $prefix . 'student_id' => [
        'required',
        new InstitutionStudentRule($this->institution)
      ],
      $prefix . 'first_assessment' => ['nullable', 'numeric', 'min:0'],
      $prefix . 'last_assessment' => ['nullable', 'numeric', 'min:0'],
      $prefix . 'result' => ['required', 'numeric', 'min:0']
    ];
  }
}
