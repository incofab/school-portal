<?php

namespace App\Http\Requests\Curriculums;

use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use App\Models\User;
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
      'teacher_user_id' => [
        'required',
        new ValidateExistsRule(User::class)
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
