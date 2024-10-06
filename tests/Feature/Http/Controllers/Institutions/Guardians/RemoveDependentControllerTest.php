<?php

use App\Models\GuardianStudent;
use App\Models\Institution;
use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;

  $this->guardianStudent = GuardianStudent::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->route = route('institutions.guardians.remove-dependent', [
    'institution' => $this->institution->uuid,
    'student' => $this->guardianStudent->student->id
  ]);
});

it('should remove dependent', function () {
  $student = $this->guardianStudent->student;

  // Making the request to remove dependent
  actingAs($this->guardianStudent->guardian)
    ->deleteJson($this->route)
    ->assertOk();

  // Verifying that the relationship was deleted
  expect(
    GuardianStudent::where(
      'guardian_user_id',
      $this->guardianStudent->guardian_user_id
    )
      ->where('student_id', $student->id)
      ->exists()
  )->toBeFalse();
});

it('should return 403 if user is not guardian of student', function () {
  // Making the request to remove dependent
  actingAs($this->instAdmin)
    ->deleteJson($this->route)
    ->assertStatus(403);
});
