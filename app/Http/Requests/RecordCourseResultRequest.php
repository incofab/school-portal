<?php

namespace App\Http\Requests;

use App\Actions\Sheet\ConvertSheetToArray;
use App\Actions\Sheet\SheetValueHandler;
use App\Enums\TermType;
use App\Models\Course;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use App\Models\Institution;
use Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Str;

class RecordCourseResultRequest extends FormRequest
{
  public Institution $institution;
  public Collection $assessments;

  protected function prepareForValidation()
  {
    $this->institution = currentInstitution();
    $courseTeacher = $this->courseTeacher;
    $this->assessments = Assessment::getAssessments(
      $this->term,
      $this->for_mid_term ?? false,
      $this->courseTeacher->classification_id
    );
    $currentUser = currentUser();
    $validTeacher =
      $currentUser->id === $courseTeacher->user_id ||
      currentInstitutionUser()->isAdmin();
    // checks if this courseTeacher's course belongs to the current institution
    if (
      !Course::query()
        ->where('id', $courseTeacher->course_id)
        ->exists() ||
      !$validTeacher
    ) {
      throw ValidationException::withMessages([
        'result' => 'Access denied'
      ]);
    }
    $this->handleFile();
  }

  protected function handleFile()
  {
    if (!$this->file) {
      return;
    }
    $columnKeyMapping = [
      'A' => 'student_id',
      'B' => 'student'
    ];
    $letterRange = range('C', 'Z');
    $i = 0;
    foreach ($this->assessments as $key => $assessment) {
      $letter = $letterRange[$i];
      $title = $assessment->raw_title;
      $columnKeyMapping[$letter] = new SheetValueHandler(
        Assessment::PREFIX . "$title",
        fn($val) => floatval($val)
      );
      $i++;
    }
    $columnKeyMapping[$letterRange[$i]] = new SheetValueHandler(
      'exam',
      fn($val) => floatval($val)
    );
    // Convert the assement dot notation to array
    $result = (new ConvertSheetToArray($this->file, $columnKeyMapping))->run();
    $formatedRes = [];
    foreach ($result as $key => $res) {
      $newRes = [];
      $ass = [];
      foreach ($res as $key => $item) {
        if (Str::startsWith($key, Assessment::PREFIX)) {
          $ass[substr($key, strlen(Assessment::PREFIX))] = $item;
        } else {
          $newRes[$key] = $item;
        }
      }
      $formatedRes[] = [...$newRes, trim(Assessment::PREFIX, '.') => $ass];
    }
    $this->merge([
      'result' => $formatedRes
    ]);
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
      ...$this->resultRule($this->courseTeacher, $this->all(), 'result.*.')
    ];
  }

  public function resultRule(
    CourseTeacher $courseTeacher,
    $data,
    string $prefix = ''
  ) {
    return [
      ...$this->assessmentValidationRule($prefix . Assessment::PREFIX),
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
      $prefix . 'student_id' => [
        'required',
        Rule::exists('students', 'id')->where(
          'classification_id',
          $courseTeacher->classification_id
        )
      ]
    ];
  }

  function assessmentValidationRule($prefix)
  {
    $rules = [];
    foreach ($this->assessments as $key => $assessment) {
      $title = $assessment->raw_title;
      $rules["{$prefix}{$title}"] = [
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
    // info('Ruless');
    // info(json_encode($rules, JSON_PRETTY_PRINT));
    return $rules;
  }
}
