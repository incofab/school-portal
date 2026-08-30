<?php

namespace App\Http\Requests;

use App\Actions\Questions\ConvertDocumentToQuestions;
use App\Actions\Questions\ConvertTextToQuestions;
use App\Actions\Sheet\ConvertSheetToArray;
use App\Models\Question;
use App\Rules\QuestionUploadFileRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UploadSessionQuestionsRequest extends FormRequest
{
  protected function prepareForValidation()
  {
    $this->handleUploadedFile();
    $this->handleQuestionSegments();
  }

  private function handleUploadedFile()
  {
    $file = $this->file('file');
    $extension = strtolower($file?->getClientOriginalExtension()) ?? '';
    if (!$file) {
      return;
    }
    if (in_array($extension, ['csv', 'xls', 'xlsx'])) {
      $this->handleExcelUpload($file);
      return;
    }
    if (in_array($extension, ['txt', 'doc', 'docx'])) {
      $this->handleDocumentUpload($file);
    }
  }

  private function handleExcelUpload($file)
  {
    try {
      $columnKeyMapping = [
        'A' => 'question_no',
        'B' => 'question',
        'C' => 'option_a',
        'D' => 'option_b',
        'E' => 'option_c',
        'F' => 'option_d',
        'G' => 'option_e',
        'H' => 'answer'
      ];
      $this->merge([
        'questions' => (new ConvertSheetToArray(
          $this->file,
          $columnKeyMapping
        ))->run()
      ]);
    } catch (\Throwable $th) {
      throw ValidationException::withMessages([
        'file' => 'Invalid file: ' . $th->getMessage()
      ]);
    }
  }

  private function handleDocumentUpload($file)
  {
    try {
      $this->merge([
        'questions' => (new ConvertDocumentToQuestions($file))->run()
      ]);
    } catch (\Throwable $th) {
      throw ValidationException::withMessages([
        'file' => 'Invalid document: ' . $th->getMessage()
      ]);
    }
  }

  private function handleQuestionSegments(): void
  {
    if ($this->hasFile('file')) {
      return;
    }

    $segments = Arr::wrap($this->input('question_segments'));
    $segments = array_values(
      array_filter(
        $segments,
        fn($segment) => $this->hasReadableContent($segment)
      )
    );

    if (!$segments || count($segments) > 6) {
      return;
    }

    try {
      $questions = [];
      $converter = new ConvertTextToQuestions();

      foreach ($segments as $segment) {
        $questions = [...$questions, ...$converter->run($segment)];
      }

      $this->merge(['questions' => $questions]);
    } catch (\Throwable $th) {
      throw ValidationException::withMessages([
        'question_segments' =>
          'Unable to process the question segments: ' . $th->getMessage()
      ]);
    }
  }

  private function hasReadableContent(mixed $segment): bool
  {
    if (!is_string($segment)) {
      return false;
    }

    $text = html_entity_decode(strip_tags($segment));
    return trim($text) !== '' || str_contains(strtolower($segment), '<img');
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
      'file' => ['nullable', 'file', new QuestionUploadFileRule()],
      'question_segments' => ['nullable', 'array', 'max:6'],
      'question_segments.*' => ['nullable', 'string'],
      'questions' => ['required', 'array', 'min:1'],
      ...Question::createRule(null, 'questions.*.')
      // 'questions.*.question' => ['required', 'string'],
      // 'questions.*.question_no' => ['required', 'string'],
      // 'questions.*.option_a' => ['required', Rule::in($options)],
      // 'questions.*.option_b' => ['required', Rule::in($options)],
      // 'questions.*.option_c' => ['required', Rule::in($options)],
      // 'questions.*.option_d' => ['required', Rule::in($options)],
      // 'questions.*.option_e' => ['nullable', Rule::in($options)],
      // 'questions.*.answer' => ['required', Rule::in($options)]
    ];
  }

  public function messages(): array
  {
    return [
      'questions.required' =>
        'Choose a question file or enter at least one question segment.',
      'questions.min' => 'Enter question content in at least one segment.',
      'question_segments.max' =>
        'You can submit a maximum of 6 question segments.'
    ];
  }
}
