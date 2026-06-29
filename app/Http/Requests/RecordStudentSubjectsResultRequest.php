<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use App\Models\Student;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class RecordStudentSubjectsResultRequest extends FormRequest
{
  public Student $student;

  protected function prepareForValidation()
  {
    $student = Student::query()
      ->whereHas('classification')
      ->find($this->integer('student_id'));
    if (!$student) {
      throw ValidationException::withMessages([
        'result' => 'Student record not found'
      ]);
    }

    $this->student = $student;
  }

  public function authorize(): bool
  {
    return true;
  }

  /**
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'institution_id' => ['required'],
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'term' => ['required', new Enum(TermType::class)],
      'for_mid_term' => ['required', 'boolean'],
      'student_id' => ['required'],
      'result' => ['required', 'array', 'min:1'],
      'result.*.course_teacher_id' => [
        'required',
        Rule::exists('course_teachers', 'id')
      ],
      ...$this->resultRules()
    ];
  }

  private function resultRules(): array
  {
    $rules = [];
    foreach ($this->input('result', []) as $index => $result) {
      $courseTeacher = CourseTeacher::query()->find(
        Arr::get($result, 'course_teacher_id')
      );
      $this->ensureCourseTeacherIsAllowed($courseTeacher);

      $prefix = "result.$index.";
      foreach ($this->assessmentRules($courseTeacher) as $key => $rule) {
        $rules[$prefix . $key] = $rule;
      }

      $rules[$prefix . 'exam'] = [
        'nullable',
        'numeric',
        'min:0',
        function ($attr, $value, $fail) use ($result) {
          $assessmentTotalScore = array_sum(Arr::get($result, 'ass', []));
          $exam = floatval($value ?? 0);
          if ($assessmentTotalScore + $exam > 100) {
            $fail('Summation of scores cannot be more than 100');
          }
        }
      ];
    }

    return $rules;
  }

  private function assessmentRules(?CourseTeacher $courseTeacher): array
  {
    if (!$courseTeacher) {
      return [];
    }

    $rules = [];
    $assessments = Assessment::getAssessments(
      $this->input('term'),
      $this->boolean('for_mid_term'),
      $courseTeacher->classification_id
    );

    foreach ($assessments as $assessment) {
      $rules['ass.' . $assessment->raw_title] = [
        'nullable',
        'numeric',
        'min:0',
        function ($attr, $value, $fail) use ($assessment) {
          if ($assessment->max && $value > $assessment->max) {
            $fail(
              "{$attr} is greater than the registered maximum ({$assessment->max})"
            );
          }
        }
      ];
    }

    return $rules;
  }

  private function ensureCourseTeacherIsAllowed(
    ?CourseTeacher $courseTeacher
  ): void {
    if (!$courseTeacher || !isset($this->student)) {
      return;
    }

    if (
      $courseTeacher->classification_id !== $this->student->classification_id
    ) {
      throw ValidationException::withMessages([
        'result' =>
          'The selected subject does not belong to this student class.'
      ]);
    }

    if (
      !currentInstitutionUser()->isAdmin() &&
      $courseTeacher->user_id !== currentUser()->id
    ) {
      throw ValidationException::withMessages([
        'result' => 'Access denied'
      ]);
    }
  }
}
