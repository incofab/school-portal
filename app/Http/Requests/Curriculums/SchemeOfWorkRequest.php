<?php

namespace App\Http\Requests\Curriculums;

use App\Enums\TermType;
use App\Models\Topic;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SchemeOfWorkRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'term' => ['required', new Enum(TermType::class)],
      'topic_id' => ['required', new ValidateExistsRule(Topic::class)],
      'week_number' => ['required', 'integer'],
      'learning_objectives' => ['nullable', 'string'],
      'resources' => ['nullable', 'string'],
      'is_used_by_institution_group' => ['required', 'boolean'],
      'institution_id' => ['nullable']
    ];
  }
}
