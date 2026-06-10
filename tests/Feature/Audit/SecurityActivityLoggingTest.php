<?php

use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Models\ActivityLog;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\postJson;

it('logs successful staff login', function () {
  $institution = Institution::factory()->create();
  $user = $institution->createdBy;

  postJson(route('login.store'), [
    'email' => $user->email,
    'password' => 'password'
  ])->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'auth.login_succeeded')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($user->id)
    ->and($log->subject_id)
    ->toBe($user->id)
    ->and($log->institution_id)
    ->toBe($institution->id)
    ->and($log->severity)
    ->toBe('security');
});

it('logs failed staff login without leaking passwords', function () {
  $user = User::factory()->create();

  postJson(route('login.store'), [
    'email' => $user->email,
    'password' => 'wrong-password'
  ])->assertStatus(422);

  $log = ActivityLog::query()
    ->where('event', 'auth.login_failed')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBeNull()
    ->and($log->severity)
    ->toBe('warning')
    ->and($log->properties->toArray())
    ->toMatchArray([
      'identifier_type' => 'email',
      'identifier' => $user->email
    ])
    ->and(json_encode($log->properties->toArray()))
    ->not->toContain('wrong-password')
    ->and(json_encode($log->properties->toArray()))
    ->not->toContain('password');
});

it('logs successful and failed student login', function () {
  $institution = Institution::factory()->create();
  $institutionUser = InstitutionUser::factory()
    ->withInstitution($institution)
    ->create(['role' => InstitutionUserType::Student]);
  $student = Student::factory()
    ->withInstitution($institution, institutionUser: $institutionUser)
    ->create();

  postJson(route('student-login.store'), [
    'student_code' => $student->code,
    'password' => 'wrong-password'
  ])->assertOk();

  expect(
    ActivityLog::query()
      ->where('event', 'auth.login_failed')
      ->where('subject_id', $student->user_id)
      ->exists()
  )->toBeTrue();

  postJson(route('student-login.store'), [
    'student_code' => $student->code,
    'password' => 'password'
  ])->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'auth.login_succeeded')
    ->where('actor_id', $student->user_id)
    ->firstOrFail();

  expect($log->institution_id)
    ->toBe($institution->id)
    ->and($log->properties['guard'])
    ->toBe('student');
});

it('logs logout', function () {
  $institution = Institution::factory()->create();
  $user = $institution->createdBy;

  actingAs($user);

  postJson(route('logout'))->assertRedirect(route('login'));

  $log = ActivityLog::query()
    ->where('event', 'auth.logout')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($user->id)
    ->and($log->institution_id)
    ->toBe($institution->id)
    ->and($log->properties['was_impersonating'])
    ->toBeFalse();
});

it(
  'logs role changes and user status changes with institution scope',
  function () {
    $institution = Institution::factory()->create();
    $admin = $institution->createdBy;
    $target = InstitutionUser::factory()
      ->teacher($institution)
      ->create();

    actingAs($admin)
      ->postJson(
        route('institutions.users.change-role', [$institution, $target]),
        [
          'role' => InstitutionUserType::Accountant->value
        ]
      )
      ->assertOk();

    actingAs($admin)
      ->postJson(
        route('institutions.institution-users.update-status', [
          $institution,
          $target
        ]),
        [
          'status' => InstitutionUserStatus::Suspended->value,
          'status_message' => 'Audit test suspension'
        ]
      )
      ->assertOk();

    $roleLog = ActivityLog::query()
      ->where('event', 'access.role_changed')
      ->firstOrFail();
    $statusLog = ActivityLog::query()
      ->where('event', 'access.user_status_changed')
      ->firstOrFail();

    expect($roleLog->actor_id)
      ->toBe($admin->id)
      ->and($roleLog->subject_id)
      ->toBe($target->user_id)
      ->and($roleLog->institution_id)
      ->toBe($institution->id)
      ->and($roleLog->old_values['role'])
      ->toBe(InstitutionUserType::Teacher->value)
      ->and($roleLog->new_values['role'])
      ->toBe(InstitutionUserType::Accountant->value)
      ->and($statusLog->institution_id)
      ->toBe($institution->id)
      ->and($statusLog->old_values['status'])
      ->toBe(InstitutionUserStatus::Active->value)
      ->and($statusLog->new_values['status'])
      ->toBe(InstitutionUserStatus::Suspended->value)
      ->and(
        ActivityLog::query()
          ->where('event', 'model.institution-user.updated')
          ->exists()
      )
      ->toBeFalse();
  }
);

it('logs admin password resets, user creation, and user deletion', function () {
  $institution = Institution::factory()->create();
  $admin = $institution->createdBy;

  actingAs($admin)
    ->postJson(route('institutions.users.store', $institution), [
      'first_name' => 'Audit',
      'last_name' => 'User',
      'email' => 'audit-user@example.test',
      'password' => 'password',
      'password_confirmation' => 'password',
      'role' => InstitutionUserType::Teacher->value
    ])
    ->assertOk();

  $createdUser = User::query()
    ->where('email', 'audit-user@example.test')
    ->firstOrFail();
  $createdInstitutionUser = $createdUser->institutionUser()->firstOrFail();

  actingAs($admin)
    ->postJson(
      route('institutions.users.reset-password', [$institution, $createdUser])
    )
    ->assertOk();

  actingAs($admin)
    ->deleteJson(
      route('institutions.users.destroy', [$institution, $createdUser])
    )
    ->assertOk();

  expect(
    ActivityLog::query()
      ->where('event', 'access.user_created')
      ->exists()
  )
    ->toBeTrue()
    ->and(
      ActivityLog::query()
        ->where('event', 'auth.password_changed')
        ->where('action', 'password_reset_by_admin')
        ->exists()
    )
    ->toBeTrue()
    ->and(
      ActivityLog::query()
        ->where('event', 'access.user_deleted')
        ->exists()
    )
    ->toBeTrue()
    ->and(
      ActivityLog::query()
        ->where('event', 'model.user.created')
        ->where('subject_id', $createdUser->id)
        ->exists()
    )
    ->toBeFalse()
    ->and(
      ActivityLog::query()
        ->where('event', 'model.institution-user.created')
        ->where('subject_id', $createdInstitutionUser->id)
        ->exists()
    )
    ->toBeFalse()
    ->and(
      ActivityLog::query()
        ->where('event', 'model.user.deleted')
        ->where('subject_id', $createdUser->id)
        ->exists()
    )
    ->toBeFalse()
    ->and(
      ActivityLog::query()
        ->where('event', 'model.institution-user.deleted')
        ->where('subject_id', $createdInstitutionUser->id)
        ->exists()
    )
    ->toBeFalse();
});

it(
  'logs manager impersonation start and stop with impersonator context',
  function () {
    $adminManager = User::factory()
      ->adminManager()
      ->create();
    $target = User::factory()
      ->teacher()
      ->create();

    actingAs($adminManager)
      ->get(route('users.impersonate', $target))
      ->assertRedirect(route('user.dashboard'));

    delete(route('users.impersonate.destroy', $target))->assertRedirect(
      route('managers.institutions.index')
    );

    $startLog = ActivityLog::query()
      ->where('event', 'access.impersonation_started')
      ->firstOrFail();
    $stopLog = ActivityLog::query()
      ->where('event', 'access.impersonation_stopped')
      ->firstOrFail();

    expect($startLog->actor_id)
      ->toBe($adminManager->id)
      ->and($startLog->subject_id)
      ->toBe($target->id)
      ->and($startLog->properties['impersonation_type'])
      ->toBe('admin_user_list')
      ->and($stopLog->actor_id)
      ->toBe($adminManager->id)
      ->and($stopLog->subject_id)
      ->toBe($target->id)
      ->and($stopLog->impersonator_id)
      ->toBe($adminManager->id);
  }
);

it('logs guardian student impersonation start and stop', function () {
  $institution = Institution::factory()->create();
  $guardian = User::factory()
    ->guardian($institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($institution)
    ->create();
  GuardianStudent::factory()
    ->withInstitution($institution)
    ->guardianUser($guardian)
    ->student($student)
    ->create();

  actingAs($guardian)
    ->get(route('institutions.guardians.impersonate', [$institution, $student]))
    ->assertRedirect(route('institutions.dashboard', $institution));

  delete(route('users.impersonate.destroy', $student->user))->assertRedirect(
    route('institutions.dashboard', $institution->id)
  );

  $startLog = ActivityLog::query()
    ->where('event', 'access.impersonation_started')
    ->firstOrFail();
  $stopLog = ActivityLog::query()
    ->where('event', 'access.impersonation_stopped')
    ->firstOrFail();

  expect($startLog->actor_id)
    ->toBe($guardian->id)
    ->and($startLog->subject_id)
    ->toBe($student->user_id)
    ->and($startLog->institution_id)
    ->toBe($institution->id)
    ->and($startLog->properties['impersonation_type'])
    ->toBe('guardian_student')
    ->and($stopLog->impersonator_id)
    ->toBe($guardian->id)
    ->and($stopLog->institution_id)
    ->toBe($institution->id);
});

it(
  'logs unauthorized access attempts at clear middleware interception points',
  function () {
    $user = User::factory()->create();

    actingAs($user)
      ->getJson(route('managers.dashboard'))
      ->assertForbidden();

    $log = ActivityLog::query()
      ->where('event', 'access.unauthorized')
      ->firstOrFail();

    expect($log->actor_id)
      ->toBe($user->id)
      ->and($log->severity)
      ->toBe('warning')
      ->and($log->description)
      ->toBe('You are not a manager');
  }
);
