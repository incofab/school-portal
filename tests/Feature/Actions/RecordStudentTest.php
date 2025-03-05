<?php

use App\Actions\RecordStudent;
use App\Enums\Gender;
use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('can create a student', function () {
  $studentData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'other_names' => 'Peter',
    'gender' => Gender::Male,
    'guardian_phone' => '08012345678',
    'code' => 'STD001',
    'classification_id' => $this->classification->id,
    'email' => 'john.doe@example.com'
  ];

  // Act
  $student = RecordStudent::make($this->institution, $studentData)->create();

  // Assert
  expect($student)->toBeInstanceOf(Student::class);
  assertDatabaseHas('users', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'other_names' => 'Peter',
    'gender' => Gender::Male,
    'email' => 'john.doe@example.com'
  ]);
  assertDatabaseHas('institution_users', [
    'user_id' => $student->user->id,
    'institution_id' => $this->institution->id,
    'role' => InstitutionUserType::Student
  ]);
  assertDatabaseHas('students', [
    'user_id' => $student->user->id,
    'institution_user_id' => $student->institutionUser->id,
    'code' => 'STD001',
    'guardian_phone' => '08012345678',
    'classification_id' => $this->classification->id
  ]);
});

it('can create a student with default student code', function () {
  $studentData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'other_names' => 'Peter',
    'gender' => Gender::Male,
    'guardian_phone' => '08012345678',
    'classification_id' => $this->classification->id,
    'email' => 'john.doe@example.com'
  ];

  // Act
  $student = RecordStudent::make($this->institution, $studentData)->create();

  // Assert
  expect($student)->toBeInstanceOf(Student::class);
  expect($student->code)->not->toBeNull();
});

it('can update a student', function () {
  $user = User::factory()->create();
  $institutionUser = InstitutionUser::factory()->create([
    'user_id' => $user->id,
    'institution_id' => $this->institution->id,
    'role' => InstitutionUserType::Student
  ]);
  $student = Student::factory()->create([
    'user_id' => $user->id,
    'institution_user_id' => $institutionUser->id,
    'code' => 'STD001',
    'classification_id' => $this->classification->id
  ]);

  $updatedStudentData = [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'other_names' => 'Mary',
    'gender' => Gender::Female,
    'guardian_phone' => '09098765432'
  ];

  // Act
  RecordStudent::make($this->institution, $updatedStudentData)->update(
    $student
  );

  // Assert
  assertDatabaseHas('users', [
    'id' => $user->id,
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'other_names' => 'Mary',
    'gender' => Gender::Female
  ]);
  assertDatabaseHas('students', [
    'user_id' => $user->id,
    'institution_user_id' => $institutionUser->id,
    'guardian_phone' => '09098765432'
  ]);
});

it('creates institution user if it does not exist', function () {
  $userData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'other_names' => 'Peter',
    'gender' => Gender::Male,
    'guardian_phone' => '08012345678',
    'code' => 'STD001',
    'classification_id' => $this->classification->id,
    'email' => 'john.doe@example.com'
  ];

  // Act
  $student = RecordStudent::make($this->institution, $userData)->create();

  // Assert
  expect($student)->toBeInstanceOf(Student::class);
  assertDatabaseHas('institution_users', [
    'user_id' => $student->user->id,
    'institution_id' => $this->institution->id,
    'role' => InstitutionUserType::Student
  ]);
});
