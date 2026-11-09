<?php

use App\Models\Institution;
use App\Models\Student;

it('resolves a student code through the institution user relationship', function () {
  $institution = Institution::factory()->create();
  $student = Student::factory()->withInstitution($institution)->create();

  $this->artisan('app:publish-student-result', ['student_code' => $student->code])
    ->expectsOutput('This student has no unpublished term results.')
    ->assertSuccessful();
});
