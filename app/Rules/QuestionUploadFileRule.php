<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class QuestionUploadFileRule implements ValidationRule
{
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $extension = strtolower($value?->getClientOriginalExtension() ?? '');
    $allowed = ['csv', 'xls', 'xlsx', 'doc', 'docx', 'txt'];

    if (!in_array($extension, $allowed)) {
      $fail('The file must be a file of type: csv, xls, xlsx, doc, docx, txt');
    }
  }
}
