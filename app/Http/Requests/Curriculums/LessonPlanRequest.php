<?php

namespace App\Http\Requests\Curriculums;

use App\Models\CourseTeacher;
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class LessonPlanRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    /** @var LessonPlan|null $lessonPlan */
    $lessonPlan = $this->route('lessonPlan');

    return [
      'course_teacher_id' => [
        'required',
        new ValidateExistsRule(CourseTeacher::class)
      ],
      'scheme_of_work_id' => [
        $lessonPlan instanceof LessonPlan ? 'nullable' : 'required',
        new ValidateExistsRule(SchemeOfWork::class)
      ],
      'objective' => ['nullable', 'string'],
      'activities' => ['nullable', 'string'],
      'content' => ['required', 'string'],
      'is_used_by_institution_group' => ['required', 'boolean'],
      'institution_id' => ['nullable']
    ];
  }
}
