<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\Course;
use App\Models\Institution;
use App\Rules\ExcelRule;
use App\Rules\InstitutionStudentRule;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
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
      'institution_id' => ['required'],
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'term' => ['required', new Enum(TermType::class)],
      'result' => ['nullable', 'array', 'min:1'],
      ...self::resultRule($this->all(), 'result.*.')
    ];
  }

  public static function resultRule($data, string $prefix = '')
  {
    $institution = currentInstitution();
    return [
      $prefix . 'first_assessment' => ['nullable', 'numeric', 'min:0'],
      $prefix . 'second_assessment' => ['nullable', 'numeric', 'min:0'],
      $prefix . 'exam' => [
        'nullable',
        'numeric',
        'min:0',
        function ($attr, $value, $fail) use ($data) {
          $examPos = strrpos($attr, 'exam');
          $arrayPrefix = substr($attr, 0, $examPos);
          $ca1 = floatval(
            Arr::get($data, $arrayPrefix . 'first_assessment', 0)
          );
          $ca2 = floatval(
            Arr::get($data, $arrayPrefix . 'second_assessment', 0)
          );
          $exam = floatval(Arr::get($data, $arrayPrefix . 'exam', 0));
          if ($ca1 + $ca2 + $exam > 100) {
            $fail('Summation of scores cannot be more than 100');
          }
        }
      ],
      $prefix . 'student_id' => [
        'required'
        // new InstitutionStudentRule($institution)
      ]
    ];
  }
}
