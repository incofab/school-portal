<?php

use App\Enums\InstitutionUserType;
use App\Models\ActivityLog;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->target = InstitutionUser::factory()
    ->teacher($this->institution)
    ->create();

  actingAs($this->admin);
});

it('allows institution admins to change a users institution type', function () {
  postJson(
    route('institutions.users.change-type', [
      $this->institution,
      $this->target
    ]),
    ['type' => InstitutionUserType::Accountant->value]
  )->assertOk();

  expect($this->target->fresh()->type)->toBe(InstitutionUserType::Accountant);

  $log = ActivityLog::query()
    ->where('event', 'access.user_type_changed')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($this->admin->id)
    ->and($log->subject_id)
    ->toBe($this->target->user_id)
    ->and($log->institution_id)
    ->toBe($this->institution->id)
    ->and($log->old_values['type'])
    ->toBe(InstitutionUserType::Teacher->value)
    ->and($log->new_values['type'])
    ->toBe(InstitutionUserType::Accountant->value);
});

it('validates the institution user type before changing it', function () {
  postJson(
    route('institutions.users.change-type', [
      $this->institution,
      $this->target
    ]),
    ['type' => 'not-a-user-type']
  )
    ->assertUnprocessable()
    ->assertJsonValidationErrors('type');

  expect($this->target->fresh()->type)->toBe(InstitutionUserType::Teacher);
});

it('does not allow non-admins to change a users institution type', function () {
  $teacher = InstitutionUser::factory()
    ->teacher($this->institution)
    ->create();

  actingAs($teacher->user)
    ->postJson(
      route('institutions.users.change-type', [
        $this->institution,
        $this->target
      ]),
      ['type' => InstitutionUserType::Accountant->value]
    )
    ->assertForbidden();

  expect($this->target->fresh()->type)->toBe(InstitutionUserType::Teacher);
});

it('prevents admins from changing a user in another institution', function () {
  $otherInstitution = Institution::factory()->create();
  $otherUser = InstitutionUser::factory()
    ->teacher($otherInstitution)
    ->create();

  postJson(
    route('institutions.users.change-type', [$this->institution, $otherUser]),
    ['type' => InstitutionUserType::Accountant->value]
  )->assertNotFound();

  expect($otherUser->fresh()->type)->toBe(InstitutionUserType::Teacher);
});

it(
  'allows institution admins to select a staff type when creating staff',
  function () {
    $teacherRoleId = $this->institution
      ->roles()
      ->where('name', InstitutionUserType::Teacher->value)
      ->value('id');

    postJson(route('institutions.users.store', $this->institution), [
      'first_name' => 'Katherine',
      'last_name' => 'Johnson',
      'email' => 'katherine.johnson@example.test',
      'password' => 'password',
      'password_confirmation' => 'password',
      'type' => InstitutionUserType::Accountant->value,
      'role' => $teacherRoleId
    ])->assertOk();

    $user = User::query()
      ->where('email', 'katherine.johnson@example.test')
      ->firstOrFail();
    $institutionUser = $user->institutionUsers()->firstOrFail();

    expect($institutionUser->type)
      ->toBe(InstitutionUserType::Accountant)
      ->and($institutionUser->roles->first()->name)
      ->toBe(InstitutionUserType::Teacher->value);
  }
);

it('rejects non-staff types when creating staff', function () {
  $teacherRoleId = $this->institution
    ->roles()
    ->where('name', InstitutionUserType::Teacher->value)
    ->value('id');

  postJson(route('institutions.users.store', $this->institution), [
    'first_name' => 'Invalid',
    'last_name' => 'Type',
    'email' => 'invalid.type@example.test',
    'password' => 'password',
    'password_confirmation' => 'password',
    'type' => InstitutionUserType::Student->value,
    'role' => $teacherRoleId
  ])
    ->assertUnprocessable()
    ->assertJsonValidationErrors('type');
});
