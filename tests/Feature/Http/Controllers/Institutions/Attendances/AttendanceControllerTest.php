<?php

use App\Enums\AttendanceType;
use App\Enums\InstitutionSettingType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\InstitutionUser;
use App\Models\TermDetail;
use App\Support\SettingsHandler;
use Carbon\Carbon;
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
  $this->academicSession = AcademicSession::factory()->create();

  InstitutionSetting::factory()
    ->term($this->institution, TermType::First->value)
    ->create();
  InstitutionSetting::factory()
    ->academicSession($this->institution, $this->academicSession)
    ->create();
  SettingsHandler::clear();

  $this->termDetail = TermDetail::factory()->create([
    'institution_id' => $this->institution->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'inactive_weekdays' => [],
    'special_active_days' => [],
    'inactive_days' => []
  ]);
});

afterEach(function () {
  Carbon::setTestNow();
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
  Carbon::setTestNow(Carbon::parse('2024-06-04 08:00:00'));
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
    'remark' => 'Arrived on time'
  ]);
});

// Test attendance store with 'sign-out' type
it(
  'allows authorized user to store a sign-out attendance record if signed in',
  function () {
    Carbon::setTestNow(Carbon::parse('2024-06-04 15:00:00'));
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
      'remark' => "$remark End of shift"
    ]);
    expect($signInAttendance->signed_out_at)->not->toBeNull();
  }
);

it('rejects attendance on inactive weekday', function () {
  Carbon::setTestNow(Carbon::parse('2024-06-03 09:00:00')); // Monday -> project weekday 0
  $this->termDetail->update(['inactive_weekdays' => [0]]);

  $payload = [
    'institution_user_id' => $this->institutionUser->id,
    'reference' => Str::orderedUuid(),
    'type' => AttendanceType::In->value,
    'remark' => 'Attempt on inactive day'
  ];

  $route = route('institutions.attendances.store', [
    'institution' => $this->institution
  ]);

  actingAs($this->admin)
    ->postJson($route, $payload)
    ->assertStatus(401)
    ->assertJson([
      'message' => 'Attendance can only be recorded on active school days.'
    ]);
});

it(
  'allows attendance on special active day within inactive weekdays',
  function () {
    Carbon::setTestNow(Carbon::parse('2024-06-09 09:00:00')); // Sunday -> project weekday 6
    $this->termDetail->update([
      'inactive_weekdays' => [6],
      'special_active_days' => [
        ['date' => '2024-06-09', 'reason' => 'Make-up class']
      ]
    ]);

    $payload = [
      'institution_user_id' => $this->institutionUser->id,
      'reference' => Str::orderedUuid(),
      'type' => AttendanceType::In->value,
      'remark' => 'Allowed special day'
    ];

    $route = route('institutions.attendances.store', [
      'institution' => $this->institution
    ]);

    actingAs($this->admin)
      ->postJson($route, $payload)
      ->assertOk();
  }
);

it(
  'rejects attendance on inactive specific day even if weekday is active',
  function () {
    Carbon::setTestNow(Carbon::parse('2024-06-04 09:00:00')); // Tuesday -> project weekday 1 (active)
    $this->termDetail->update([
      'inactive_weekdays' => [],
      'inactive_days' => [['date' => '2024-06-04', 'reason' => 'Holiday']]
    ]);

    $payload = [
      'institution_user_id' => $this->institutionUser->id,
      'reference' => Str::orderedUuid(),
      'type' => AttendanceType::In->value,
      'remark' => 'Attempt on inactive date'
    ];

    $route = route('institutions.attendances.store', [
      'institution' => $this->institution
    ]);

    actingAs($this->admin)
      ->postJson($route, $payload)
      ->assertStatus(401)
      ->assertJson([
        'message' => 'Attendance can only be recorded on active school days.'
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
      ->assertStatus(401)
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
