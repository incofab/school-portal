<?php

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('allows institution staff members to be assigned subjects', function () {
  foreach (
    [
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher,
      InstitutionUserType::Accountant,
      InstitutionUserType::Others
    ] as $type
  ) {
    $user = User::factory()->create();
    InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->user($user)
      ->type($type)
      ->create();

    actingAs($this->admin)
      ->postJson(
        route('institutions.course-teachers.store', [
          $this->institution->uuid,
          $user
        ]),
        [
          'course_ids' => [$this->course->id],
          'classification_ids' => [$this->classification->id]
        ]
      )
      ->assertOk();

    expect(
      CourseTeacher::query()
        ->where('user_id', $user->id)
        ->where('course_id', $this->course->id)
        ->exists()
    )->toBeTrue();
  }
});

it(
  'does not allow non-staff or staff from another institution to be assigned',
  function () {
    $student = User::factory()->create();
    InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->user($student)
      ->type(InstitutionUserType::Student)
      ->create();

    $otherInstitution = Institution::factory()->create();
    $otherStaff = User::factory()->create();
    InstitutionUser::factory()
      ->withInstitution($otherInstitution)
      ->user($otherStaff)
      ->type(InstitutionUserType::Accountant)
      ->create();

    foreach ([$student, $otherStaff] as $user) {
      actingAs($this->admin)
        ->postJson(
          route('institutions.course-teachers.store', [
            $this->institution->uuid,
            $user
          ]),
          [
            'course_ids' => [$this->course->id],
            'classification_ids' => [$this->classification->id]
          ]
        )
        ->assertForbidden();
    }

    expect(CourseTeacher::query()->count())->toBe(0);
  }
);
