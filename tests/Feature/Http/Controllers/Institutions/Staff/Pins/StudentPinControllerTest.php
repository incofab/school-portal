<?php

use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Support\SettingsHandler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  SettingsHandler::clear();
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();

  InstitutionSetting::factory()
    ->academicSession($this->institution, $this->academicSession)
    ->create();
  InstitutionSetting::factory()
    ->term($this->institution)
    ->create();
});

it('can store student pin', function () {
  $student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  // Mock recordStudentPin method
  //   $controller = new \App\Http\Controllers\Institutions\Staff\Pins\StudentPinController();
  //   $controller->recordStudentPin = function () {};

  // Hit the API endpoint
  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.pins.students.store', [
        $this->institution->uuid,
        $student->id
      ])
    )
    ->assertOk();

  // Assert pin is created
  $this->assertDatabaseHas('pins', [
    'student_id' => $student->id,
    'institution_id' => $this->institution->id
  ]);
});

it('can store class student pins', function () {
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  Student::factory(5)
    ->withInstitution($this->institution)
    ->create();

  // Hit the API endpoint
  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.pins.classifications.store', [
        $this->institution->uuid,
        $classification->id
      ])
    )
    ->assertOk();

  // Check that double call has no effect
  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.pins.classifications.store', [
        $this->institution->uuid,
        $classification->id
      ])
    )
    ->assertOk();

  $students = $classification->students;
  assertDatabaseCount('pins', $students->count());
  // Assert pins are created for all students
  foreach ($students as $student) {
    assertDatabaseHas('pins', ['student_id' => $student->id]);
  }
});
