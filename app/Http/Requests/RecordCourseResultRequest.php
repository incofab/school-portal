<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\Course;
use App\Models\Assessment;
use App\Models\Institution;
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
      'for_mid_term' => ['required', 'boolean'],
      'result' => ['nullable', 'array', 'min:1'],
      ...$this->resultRule($this->all(), 'result.*.')
    ];
  }

  public function resultRule($data, string $prefix = '')
  {
    return [
      ...$this->assessmentValidationRule("{$prefix}ass."),
      $prefix . 'exam' => [
        'sometimes',
        'numeric',
        'min:0',
        function ($attr, $value, $fail) use ($data) {
          $examPos = strrpos($attr, 'exam');
          $arrayPrefix = substr($attr, 0, $examPos);

          $assessments = Arr::get($data, $arrayPrefix . 'ass', []);
          $assessmentsTotalScore = array_sum($assessments);

          $exam = floatval(Arr::get($data, $arrayPrefix . 'exam', 0));
          if ($assessmentsTotalScore + $exam > 100) {
            $fail('Summation of scores cannot be more than 100');
          }
        }
      ],
      $prefix . 'student_id' => ['required']
    ];
  }

  function assessmentValidationRule($prefix)
  {
    $assessments = Assessment::query()
      ->forMidTerm($this->for_mid_term)
      ->forTerm($this->term)
      ->get();

    $rules = [];

    foreach ($assessments as $key => $assessment) {
      $rules["{$prefix}{$assessment->title}"] = [
        'sometimes',
        'numeric',
        'min:0',
        function ($attr, $value, $fail) use ($assessment) {
          if ($assessment->max && $value > $assessment->max) {
            $fail(
              "{$attr} is greater than the registered maximum ({$assessment->max})"
            );
            return;
          }
        }
      ];
    }
    return $rules;
  }
}
