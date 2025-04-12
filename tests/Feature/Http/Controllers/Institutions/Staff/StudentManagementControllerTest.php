<?php

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

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

// --- Testing for delete ---
it('deletes a student successfully as admin if no results exist', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $courseResult = CourseResult::factory()
    ->withInstitution($this->institution)
    ->create(['student_id' => $student->id]);
  $user = $student->user;
  $institutionUser = $student->institutionUser;

  // Ensure no results exist initially
  expect($student->termResults()->count())->toBe(0);

  actingAs($this->instAdmin)
    ->deleteJson(
      route('institutions.students.destroy', [
        $this->institution->uuid,
        $student->id
      ])
    )
    ->assertOk();
  // Assert student is soft deleted
  assertSoftDeleted('students', ['id' => $student->id]);
  assertSoftDeleted('institution_users', ['id' => $institutionUser->id]);
  assertDatabaseMissing('course_results', ['id' => $courseResult->id]);
  // Assert user is soft deleted (since it was their only institution link)
  assertSoftDeleted('users', ['id' => $user->id]);
});

it(
  'does not delete the user if they belong to another institution',
  function () {
    // Create student and user in the first institution
    $student1 = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();
    $user = $student1->user;
    $institutionUser1 = $student1->institutionUser;

    // Create a second institution and link the same user (e.g., as a teacher)
    $institution2 = Institution::factory()->create();
    $institutionUser2 = InstitutionUser::factory()
      ->for($institution2)
      ->for($user)
      ->create(['role' => InstitutionUserType::Teacher]);

    actingAs($this->instAdmin) // Admin of the first institution
      ->deleteJson(
        route('institutions.students.destroy', [
          $this->institution->uuid,
          $student1->id
        ])
      )
      ->assertOk();

    // Assert student is soft deleted
    assertSoftDeleted('students', ['id' => $student1->id]);
    // Assert institution user for the first institution is deleted
    assertSoftDeleted('institution_users', ['id' => $institutionUser1->id]);
    // Assert user is NOT deleted
    assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    // Assert institution user for the second institution still exists
    assertDatabaseHas('institution_users', ['id' => $institutionUser2->id]);
  }
);

it('prevents student deletion if term results exist', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  // Create a term result for the student
  TermResult::factory()
    ->forStudent($student)
    ->create();

  expect($student->termResults()->count())->toBeGreaterThan(0);

  actingAs($this->instAdmin)
    ->deleteJson(
      route('institutions.students.destroy', [
        $this->institution->uuid,
        $student->id
      ])
    )
    ->assertStatus(403) // Expect Forbidden
    ->assertJsonFragment([
      'message' => 'This student has existing results, move to alumni instead'
    ]);

  // Assert student was NOT deleted
  assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
});

it('prevents student deletion by non-admin users', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $teacherUser = User::factory()
    ->teacher($this->institution)
    ->create(); // Create a teacher

  actingAs($teacherUser)
    ->deleteJson(
      route('institutions.students.destroy', [
        $this->institution->uuid,
        $student->id
      ])
    )
    ->assertForbidden(); // Expect Forbidden

  // Assert student was NOT deleted
  assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
});
