<?php

use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->students = Student::factory(5)
    ->withInstitution($this->institution)
    ->create();
  $this->instAdmin = $this->institution->createdBy;
});

it('creates a new student with valid data', function () {
  $payload = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'gender' => 'male',
    'email' => 'john.doe@example.com',
    'phone' => '1234567890',
    'guardian_phone' => '0987654321',
    'classification_id' => $this->classification->id,
    'password' => 'password',
    'password_confirmation' => 'password'
  ];

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.students.store', $this->institution->uuid),
      $payload
    )
    ->assertOk();

  assertDatabaseHas('users', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'gender' => 'male',
    'email' => 'john.doe@example.com'
  ]);
  $createdUser = User::where('email', 'john.doe@example.com')->first();
  assertDatabaseHas('students', [
    'user_id' => $createdUser->id,
    'classification_id' => $this->classification->id,
    'guardian_phone' => '0987654321'
  ]);
});

it('updates student code with correct payload', function () {
  $student = Student::factory()
    ->withInstitution($this->institution)
    ->create(['code' => 'OLDCODE456']);
  $newCode = 'NEWCODE456';
  $payload = [
    'code' => $newCode
  ];

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.students.update-code', [
        $this->institution->uuid,
        $student->id
      ]),
      $payload
    )
    ->assertOk();

  assertDatabaseHas('students', [
    'id' => $student->id,
    'code' => $newCode
  ]);
});
