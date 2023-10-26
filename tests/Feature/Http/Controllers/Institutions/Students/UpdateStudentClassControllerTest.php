<?php

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Http\Response;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->route = route('institutions.classifications.migrate-students', [
    $this->institution->uuid,
    $this->classification
  ]);
});

it('moves students in a class to another class', function () {
  [$destinationClass, $classWithStudents] = Classification::factory()
    ->withInstitution($this->institution)
    ->count(2)
    ->create();
  $numOfStudents = 10;
  Student::factory()
    ->count($numOfStudents)
    ->withInstitution($this->institution, $this->classification)
    ->create();
  Student::factory()
    ->count(5)
    ->withInstitution($this->institution, $classWithStudents)
    ->create();

  // echo 'Tests: requires destination_class when we are not moving the students to alumni\n';
  $requestData = ['destination_class' => null, 'move_to_alumni' => false];
  actingAs($this->instAdmin)
    ->postJson($this->route, $requestData)
    ->assertJsonValidationErrorFor('destination_class');

  // echo 'Tests: Cannot move students to classes that already has a student\n';
  $requestData = ['destination_class' => $classWithStudents->id];
  actingAs($this->instAdmin)
    ->postJson($this->route, $requestData)
    ->assertStatus(Response::HTTP_NOT_ACCEPTABLE);

  // echo 'Tests: Move student from one class to another\n';
  $requestData = ['destination_class' => $destinationClass->id];
  actingAs($this->instAdmin)
    ->postJson($this->route, $requestData)
    ->assertOk();

  expect($destinationClass->students()->count())->toBe($numOfStudents);
  expect($this->classification->students()->count())->toBe(0);
});

it('moves students in a class to alumni', function () {
  $numOfStudents = 10;
  [$student1] = Student::factory()
    ->count($numOfStudents)
    ->withInstitution($this->institution, $this->classification)
    ->create();
  expect($this->classification->students()->count())->toBe($numOfStudents);
  $requestData = ['move_to_alumni' => true];
  actingAs($this->instAdmin)
    ->postJson($this->route, $requestData)
    ->assertOk();

  expect($this->classification->students()->count())->toBe(0);
  expect($student1->fresh()->classification_id)->toBe(null);
});

it('changes student\'s class to another one', function () {
  $destinationClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $changeStudentRoute = route('institutions.students.change-class', [
    $this->institution->uuid,
    $student
  ]);
  //   expect($student->institutionUser->role)->toBe(InstitutionUserType::Student);

  //echo 'Tests: requires destination_class when we are not moving the student to alumni\n';
  $requestData = ['destination_class' => null, 'move_to_alumni' => false];
  actingAs($this->instAdmin)
    ->postJson($changeStudentRoute, $requestData)
    ->assertJsonValidationErrorFor('destination_class');

  echo 'Tests: change student class to another one\n';
  $requestData = ['destination_class' => $destinationClass->id];
  actingAs($this->instAdmin)
    ->postJson($changeStudentRoute, $requestData)
    ->assertOk();

  $student = $student->fresh();
  expect($student->classification_id)->toBe($destinationClass->id);
});

it('moves a student in a class to alumni', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $changeStudentRoute = route('institutions.students.change-class', [
    $this->institution->uuid,
    $student
  ]);
  expect($student->institutionUser->role)->toBe(InstitutionUserType::Student);

  $requestData = ['move_to_alumni' => true];
  actingAs($this->instAdmin)
    ->postJson($changeStudentRoute, $requestData)
    ->assertOk();

  $student = $student->fresh();
  expect($student->classification_id)->toBe(null);
  expect($student->institutionUser->role)->toBe(InstitutionUserType::Alumni);
});
