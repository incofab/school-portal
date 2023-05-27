<?php

namespace App\Rules;

use App\Models\Institution;
use App\Models\Student;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstitutionStudentRule implements ValidationRule
{
  public function __construct(private Institution $institution)
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
    $student = Student::query()
      ->join(
        'institution_users',
        'institution_users.user_id',
        'students.user_id'
      )
      ->where('students.id', $value)
      ->where('institution_users.institution_id', $this->institution->id)
      ->first();

    if (!$student) {
      $fail('student does not exist');
      return;
    }
  }
}
