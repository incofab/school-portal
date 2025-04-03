<?php

namespace App\Http\Requests;

use App\Actions\Sheet\ConvertSheetToArray;
use App\Models\Question;
use App\Rules\ExcelRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UploadSessionQuestionsRequest extends FormRequest
{
  protected function prepareForValidation()
  {
    $this->handleUploadedFile();
  }

  private function handleUploadedFile()
  {
    $file = $this->file('file');
    $extension = strtolower($file?->getClientOriginalExtension()) ?? '';
    if (!$file || !in_array($extension, ['csv', 'xls', 'xlsx'])) {
      return;
    }
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
      'file' => ['required', 'file', new ExcelRule($this->file('file'))],
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
}
