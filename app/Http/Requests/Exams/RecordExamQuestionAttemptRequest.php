<?php

namespace App\Http\Requests\Exams;

use Illuminate\Foundation\Http\FormRequest;

class RecordExamQuestionAttemptRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'attempts' => ['required', 'array', 'min:1'],
      'attempts.*' => ['nullable', 'string', 'max:10000'],
      'current_question_index' => ['nullable', 'integer', 'min:0']
    ];
  }
}
