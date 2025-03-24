<?php

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

/**
 * ./vendor/bin/pest --filter AttendanceControllerTest
 */

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();
});

// Test the create attendance view renders properly
it('renders the create attendance view for authorized users', function () {
  $route = route('institutions.attendances.create', [
    'institution' => $this->institution->uuid
  ]);

  actingAs($this->admin)
    ->get($route)
    ->assertOk();
});

// Test attendance store with 'sign-in' type
it('allows authorized user to store a sign-in attendance record', function () {
  $institutionStaffUser = $this->admin
    ->institutionUsers()
    ->where('institution_id', $this->institution->id)
    ->first();

  $payload = [
    'institution_user_id' => $this->institutionUser->id,
    'reference' => Str::orderedUuid(),
    'type' => AttendanceType::In->value,
    'remark' => 'Arrived on time'
  ];

  $route = route('institutions.attendances.store', [
    'institution' => $this->institution
  ]);

  actingAs($this->admin)
    ->postJson($route, $payload)
    ->assertOk();

  assertDatabaseHas('attendances', [
    'institution_id' => $this->institution->id,
    'institution_user_id' => $this->institutionUser->id,
    'institution_staff_user_id' => $institutionStaffUser->id,
    'remark' => 'Arrived on time',
    'signed_in_at' => now(),
    'signed_out_at' => null
  ]);
});

// Test attendance store with 'sign-out' type
it(
  'allows authorized user to store a sign-out attendance record if signed in',
  function () {
    $remark = 'Existing remark';
    $signInAttendance = Attendance::factory()
      ->signedInOnly()
      ->institutionUser($this->institutionUser)
      ->create([
        'remark' => $remark
      ]);

    $payload = [
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->institutionUser->id,
      'type' => AttendanceType::Out->value,
      'remark' => 'End of shift'
    ];

    $route = route('institutions.attendances.store', [
      'institution' => $this->institution->uuid
    ]);

    actingAs($this->admin)
      ->postJson($route, $payload)
      ->assertOk();

    $signInAttendance->refresh();
    assertDatabaseHas('attendances', [
      'id' => $signInAttendance->id,
      'remark' => "$remark End of shift",
      'signed_out_at' => now()
    ]);
  }
);

// Test attendance store with 'sign-out' when no 'signed-in' record exists
it(
  'returns an error when signing out without a previous signed-in record',
  function () {
    $payload = [
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->institutionUser->id,
      'type' => AttendanceType::Out->value,
      'remark' => 'End of shift'
    ];

    $route = route('institutions.attendances.store', [
      'institution' => $this->institution->uuid
    ]);

    actingAs($this->admin)
      ->postJson($route, $payload)
      ->assertStatus(400)
      ->assertJson(['message' => 'No Signed-In Record Found.']);
  }
);

// Test attendance record deletion by an admin user
it('allows an admin to delete an attendance record', function () {
  $attendance = Attendance::factory()->create([
    'institution_id' => $this->institution->id,
    'institution_user_id' => $this->institutionUser->id,
    'signed_in_at' => now()
  ]);

  $route = route('institutions.attendances.destroy', [
    'institution' => $this->institution->uuid,
    'attendance' => $attendance->id
  ]);

  actingAs($this->admin)
    ->deleteJson($route)
    ->assertOk();

  assertSoftDeleted('attendances', ['id' => $attendance->id]);
});

// Test unauthorized deletion attempt by a non-admin user
it('prevents a non-admin user from deleting an attendance record', function () {
  $attendance = Attendance::factory()->create([
    'institution_id' => $this->institution->id,
    'institution_user_id' => $this->institutionUser->id
  ]);

  $route = route('institutions.attendances.destroy', [
    'institution' => $this->institution->uuid,
    'attendance' => $attendance->id
  ]);

  actingAs($this->institutionUser->user)
    ->deleteJson($route)
    ->assertStatus(403);
});
