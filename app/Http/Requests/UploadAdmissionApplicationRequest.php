<?php

namespace App\Http\Requests;

use App\Actions\Admisssions\RecordAdmissionApplication;
use App\Actions\Sheet\ConvertSheetToArray;
use App\Models\AdmissionApplication;
use App\Rules\ExcelRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UploadAdmissionApplicationRequest extends FormRequest
{
  function prepareForValidation()
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
      $columnKeyMapping = RecordAdmissionApplication::$sheetColumnMapping;
      $this->merge([
        'applications' => (new ConvertSheetToArray(
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
      'applications' => ['required', 'array', 'min:1'],
      'reference' => ['required', 'string'],
      ...collect(AdmissionApplication::createRule('applications.*.'))->except(
        'applications.*.reference',
        'applications.*.admission_form_id'
      )
    ];
  }
}
