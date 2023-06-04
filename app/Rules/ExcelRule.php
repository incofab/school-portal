<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ExcelRule implements ValidationRule
{
  public function __construct(private UploadedFile $file)
  {
    //
  }

  /**
   * Run the validation rule.
   *
   * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
   */
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $extension = strtolower($this->file?->getClientOriginalExtension()) ?? '';

    $isValidExtension = in_array($extension, ['csv', 'xls', 'xlsx']);

    if (!$isValidExtension) {
      $fail('The excel file must be a file of type: csv, xls, xlsx');
      return;
    }
  }
}
