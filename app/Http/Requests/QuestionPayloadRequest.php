<?php

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class QuestionPayloadRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    /** @var Question|null $question */
    $question = $this->route('question');
    return Question::createRule($question);
  }

  protected function prepareForValidation(): void
  {
    if (!$this->hasFile('question_payload')) {
      return;
    }

    $file = $this->file('question_payload');
    if (!$file->isValid()) {
      throw ValidationException::withMessages([
        'question_payload' => 'Question payload upload failed.'
      ]);
    }

    $decoded = json_decode($file->get(), true);
    if (!is_array($decoded)) {
      throw ValidationException::withMessages([
        'question_payload' => 'Invalid question payload file.'
      ]);
    }

    $this->replace($decoded);
  }
}
