<?php

use App\Enums\AttendanceType;
use App\Enums\InstitutionSettingType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\InstitutionUser;
use App\Models\Student;
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

it('allows authorized user to bulk sign in students in a class', function () {
  Carbon::setTestNow(Carbon::parse('2024-06-04 08:00:00'));
  $institutionStaffUser = $this->admin
    ->institutionUsers()
    ->where('institution_id', $this->institution->id)
    ->first();
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $students = Student::factory()
    ->count(3)
    ->withInstitution($this->institution, $classification)
    ->create();

  $payload = [
    'institution_user_ids' => $students
      ->pluck('institution_user_id')
      ->all(),
    'unmark_institution_user_ids' => [],
    'type' => AttendanceType::In->value,
    'remark' => 'Morning register'
  ];

  $route = route('institutions.attendances.bulk-store', [
    'institution' => $this->institution
  ]);

  actingAs($this->admin)
    ->postJson($route, $payload)
    ->assertOk()
    ->assertJson([
      'recorded_count' => 3,
      'unmarked_count' => 0,
      'skipped_count' => 0,
      'failed_count' => 0
    ]);

  foreach ($students as $student) {
    assertDatabaseHas('attendances', [
      'institution_id' => $this->institution->id,
      'institution_user_id' => $student->institution_user_id,
      'institution_staff_user_id' => $institutionStaffUser->id,
      'remark' => 'Morning register'
    ]);
  }
});

it('loads students for a class group with attendance status', function () {
  Carbon::setTestNow(Carbon::parse('2024-06-04 08:00:00'));
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $otherClassification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $classification)
    ->create();
  Student::factory()
    ->withInstitution($this->institution, $otherClassification)
    ->create();

  Attendance::factory()
    ->signedInOnly()
    ->institutionUser($student->institutionUser)
    ->create();

  $route = route('institutions.attendances.students', [
    'institution' => $this->institution,
    'classification_group_id' => $classification->classification_group_id
  ]);

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertJsonCount(1, 'result')
    ->assertJsonPath('result.0.id', $student->institution_user_id)
    ->assertJsonPath('result.0.attendance_status.checked_in', true)
    ->assertJsonPath('result.0.attendance_status.checked_out', false);
});

it('loads students for a selected class within a class group', function () {
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $otherClassification = Classification::factory()
    ->classificationGroup($classification->classificationGroup)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $classification)
    ->create();
  Student::factory()
    ->withInstitution($this->institution, $otherClassification)
    ->create();

  $route = route('institutions.attendances.students', [
    'institution' => $this->institution,
    'classification_group_id' => $classification->classification_group_id,
    'classification_id' => $classification->id
  ]);

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertJsonCount(1, 'result')
    ->assertJsonPath('result.0.id', $student->institution_user_id);
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

it('allows authorized user to bulk sign out staff', function () {
  Carbon::setTestNow(Carbon::parse('2024-06-04 15:00:00'));
  $staff = InstitutionUser::factory()
    ->count(2)
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  $staff->each(
    fn(InstitutionUser $institutionUser) => Attendance::factory()
      ->signedInOnly()
      ->institutionUser($institutionUser)
      ->create(['remark' => 'Signed in'])
  );

  $payload = [
    'institution_user_ids' => $staff->pluck('id')->all(),
    'unmark_institution_user_ids' => [],
    'type' => AttendanceType::Out->value,
    'remark' => 'Closed register'
  ];

  $route = route('institutions.attendances.bulk-store', [
    'institution' => $this->institution->uuid
  ]);

  actingAs($this->admin)
    ->postJson($route, $payload)
    ->assertOk()
    ->assertJson([
      'recorded_count' => 2,
      'unmarked_count' => 0,
      'skipped_count' => 0,
      'failed_count' => 0
    ]);

  foreach ($staff as $institutionUser) {
    $attendance = Attendance::where(
      'institution_user_id',
      $institutionUser->id
    )->first();

    expect($attendance->signed_out_at)->not->toBeNull();
    expect($attendance->remark)->toBe('Signed in Closed register');
  }
});

it('bulk sign in unchecks visible users by deleting open check-in records', function () {
  Carbon::setTestNow(Carbon::parse('2024-06-04 09:00:00'));
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $students = Student::factory()
    ->count(2)
    ->withInstitution($this->institution, $classification)
    ->create();

  $attendanceToRemove = Attendance::factory()
    ->signedInOnly()
    ->institutionUser($students[0]->institutionUser)
    ->create(['signed_in_at' => now()]);
  $attendanceToKeep = Attendance::factory()
    ->signedInOnly()
    ->institutionUser($students[1]->institutionUser)
    ->create(['signed_in_at' => now()]);

  $payload = [
    'institution_user_ids' => [],
    'unmark_institution_user_ids' => [$students[0]->institution_user_id],
    'type' => AttendanceType::In->value,
    'remark' => 'Adjusted register'
  ];

  $route = route('institutions.attendances.bulk-store', [
    'institution' => $this->institution
  ]);

  actingAs($this->admin)
    ->postJson($route, $payload)
    ->assertOk()
    ->assertJson([
      'recorded_count' => 0,
      'unmarked_count' => 1
    ]);

  assertSoftDeleted('attendances', ['id' => $attendanceToRemove->id]);
  assertDatabaseHas('attendances', [
    'id' => $attendanceToKeep->id,
    'deleted_at' => null
  ]);
});

it('bulk sign in does not delete checked-out records when unchecked', function () {
  Carbon::setTestNow(Carbon::parse('2024-06-04 09:00:00'));
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $classification)
    ->create();
  $attendance = Attendance::factory()
    ->institutionUser($student->institutionUser)
    ->create([
      'signed_in_at' => now()->subHour(),
      'signed_out_at' => now()
    ]);

  $payload = [
    'institution_user_ids' => [],
    'unmark_institution_user_ids' => [$student->institution_user_id],
    'type' => AttendanceType::In->value,
    'remark' => 'Adjusted register'
  ];

  $route = route('institutions.attendances.bulk-store', [
    'institution' => $this->institution
  ]);

  actingAs($this->admin)
    ->postJson($route, $payload)
    ->assertOk()
    ->assertJson([
      'recorded_count' => 0,
      'unmarked_count' => 0
    ]);

  assertDatabaseHas('attendances', [
    'id' => $attendance->id,
    'deleted_at' => null
  ]);
});

it('bulk sign out unchecks visible users by clearing signed out time', function () {
  Carbon::setTestNow(Carbon::parse('2024-06-04 16:00:00'));
  $staff = InstitutionUser::factory()
    ->count(2)
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  $attendanceToReopen = Attendance::factory()
    ->institutionUser($staff[0])
    ->create([
      'signed_in_at' => now()->subHours(7),
      'signed_out_at' => now()
    ]);
  $attendanceToKeepClosed = Attendance::factory()
    ->institutionUser($staff[1])
    ->create([
      'signed_in_at' => now()->subHours(7),
      'signed_out_at' => now()
    ]);

  $payload = [
    'institution_user_ids' => [],
    'unmark_institution_user_ids' => [$staff[0]->id],
    'type' => AttendanceType::Out->value,
    'remark' => 'Adjusted closing register'
  ];

  $route = route('institutions.attendances.bulk-store', [
    'institution' => $this->institution
  ]);

  actingAs($this->admin)
    ->postJson($route, $payload)
    ->assertOk()
    ->assertJson([
      'recorded_count' => 0,
      'unmarked_count' => 1
    ]);

  expect($attendanceToReopen->refresh()->signed_out_at)->toBeNull();
  expect($attendanceToKeepClosed->refresh()->signed_out_at)->not->toBeNull();
});

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
