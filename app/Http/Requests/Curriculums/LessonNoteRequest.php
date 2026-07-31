<?php

namespace App\Http\Requests\Curriculums;

use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class LessonNoteRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    /** @var LessonNote|null $lessonNote */
    $lessonNote = $this->route('lessonNote');

    return [
      'lesson_plan_id' => [
        $lessonNote instanceof LessonNote ? 'nullable' : 'required',
        new ValidateExistsRule(LessonPlan::class)
      ],
      'title' => ['required', 'string', 'max:255'],
      'content' => ['required', 'string'],
      'is_published' => ['required', 'boolean'],
      'is_used_by_institution_group' => ['required', 'boolean'],
      'is_used_by_classification_group' => ['required', 'boolean'],
      'institution_id' => ['nullable']
    ];
  }
}
