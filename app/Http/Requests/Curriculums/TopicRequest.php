<?php

namespace App\Http\Requests\Curriculums;

use App\Enums\TermType;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Rules\ValidateExistsRule;
use App\Rules\ValidateUniqueRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TopicRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    /** @var Topic|null $topic */
    $topic = $this->route('topic');
    $isUpdate = $topic instanceof Topic;

    return [
      'course_id' => ['required', new ValidateExistsRule(Course::class)],
      'title' => [
        'required',
        'string',
        'max:255',
        (new ValidateUniqueRule(Topic::class, 'title'))->ignore($topic?->id)
      ],
      'term' => [
        $isUpdate ? 'nullable' : 'required',
        new Enum(TermType::class)
      ],
      'week_number' => [$isUpdate ? 'nullable' : 'required', 'integer'],
      'user_id' => ['nullable', new ValidateExistsRule(User::class)],
      'description' => ['nullable', 'string'],
      'classification_group_id' => [
        'required',
        new ValidateExistsRule(ClassificationGroup::class)
      ],
      'parent_topic_id' => ['nullable', new ValidateExistsRule(Topic::class)],
      'is_used_by_institution_group' => ['required', 'boolean'],
      'institution_id' => ['nullable'],
      'lesson_plan_files' => ['nullable', 'array'],
      'lesson_plan_files.*' => [
        'file',
        'mimes:jpg,jpeg,png,webp,pdf,doc,docx,mp4,mov,avi,mkv,mp3,wav',
        'max:10240'
      ]
    ];
  }
}
