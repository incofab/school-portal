<?php

namespace App\Actions\Payments;

use App\Models\Institution;
use App\Models\Student;

class FindPublicStudent
{
  public function __construct(
    private Institution $institution,
    private string $studentCode
  ) {
  }

  public static function make(Institution $institution, string $studentCode)
  {
    return new self($institution, $studentCode);
  }

  public function run(): Student
  {
    $student = Student::query()
      ->where('code', $this->studentCode)
      ->with('institutionUser')
      ->firstOrFail();

    abort_unless(
      $student->institutionUser?->institution_id === $this->institution->id,
      404,
      'Student not found.'
    );

    return $student;
  }
}
