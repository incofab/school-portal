<?php

use App\Enums\InstitutionUserType;
use App\Enums\AttendanceType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\InstitutionUser;
use App\Models\TermDetail;
use App\Support\SettingsHandler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->academicSession = AcademicSession::factory()->create();

  InstitutionSetting::factory()
    ->term($this->institution, TermType::First->value)
    ->create();
  InstitutionSetting::factory()
    ->academicSession($this->institution, $this->academicSession)
    ->create();
  TermDetail::factory()->create([
    'institution_id' => $this->institution->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'inactive_weekdays' => [],
    'special_active_days' => [],
    'inactive_days' => []
  ]);

  SettingsHandler::clear();
});

afterEach(function () {
  Carbon::setTestNow();
  SettingsHandler::clear();
});

it('records attendance for the authenticated institution user', function () {
  Carbon::setTestNow(Carbon::parse('2026-08-29 08:15:42'));
  $institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();
  $otherInstitutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  $response = actingAs($institutionUser->user, 'sanctum')->postJson(
    route('api.institutions.attendance.self', [
      'institution' => $this->institution->code
    ]),
    [
      'institution_user_id' => $otherInstitutionUser->id,
      'reference' => Str::orderedUuid()->toString(),
      'type' => AttendanceType::In->value,
      'remark' => 'Scanned school attendance QR code'
    ]
  );

  $response->assertOk()->assertJson([
    'success' => true,
    'status' => 'recorded'
  ]);

  $attendance = Attendance::query()
    ->where('institution_user_id', $institutionUser->id)
    ->firstOrFail();

  expect($attendance->institution_staff_user_id)
    ->toBe($institutionUser->id)
    ->and($attendance->remark)
    ->toBe('Scanned school attendance QR code')
    ->and($attendance->signed_in_at->toDateTimeString())
    ->toBe('2026-08-29 08:15:42')
    ->and($attendance->reference)
    ->not->toBeNull();

  expect(
    Attendance::query()
      ->where('institution_user_id', $otherInstitutionUser->id)
      ->exists()
  )->toBeFalse();

  actingAs($institutionUser->user, 'sanctum')
    ->postJson(
      route('api.institutions.attendance.self', [
        'institution' => $this->institution->code
      ]),
      [
        'type' => AttendanceType::In->value,
        'reference' => Str::orderedUuid()->toString()
      ]
    )
    ->assertOk()
    ->assertJsonPath('status', 'skipped');

  expect(
    Attendance::query()
      ->where('institution_user_id', $institutionUser->id)
      ->count()
  )->toBe(1);
});

it(
  'supports accountants and an optional server-side attendance datetime',
  function () {
    Carbon::setTestNow(Carbon::parse('2026-08-29 17:10:00'));
    $institutionUser = InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->state(['role' => InstitutionUserType::Accountant->value])
      ->create();

    actingAs($institutionUser->user, 'sanctum')
      ->postJson(
        route('api.institutions.attendance.self', [
          'institution' => $this->institution->code
        ]),
        [
          'datetime' => '2026-08-28 07:30:00',
          'type' => AttendanceType::In->value,
          'reference' => Str::orderedUuid()->toString()
        ]
      )
      ->assertOk()
      ->assertJsonPath('status', 'recorded');

    $attendance = Attendance::query()
      ->where('institution_user_id', $institutionUser->id)
      ->firstOrFail();

    expect($attendance->signed_in_at->toDateTimeString())->toBe(
      '2026-08-28 07:30:00'
    );
  }
);

it(
  'allows the authenticated institution user to sign out through the self attendance endpoint',
  function () {
    Carbon::setTestNow(Carbon::parse('2026-08-29 17:00:00'));
    $institutionUser = InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->teacher()
      ->create();
    $attendance = Attendance::factory()
      ->institutionUser($institutionUser)
      ->signedInOnly()
      ->create([
        'signed_in_at' => Carbon::parse('2026-08-29 08:00:00'),
        'remark' => 'Morning check-in'
      ]);

    actingAs($institutionUser->user, 'sanctum')
      ->postJson(
        route('api.institutions.attendance.self', [
          'institution' => $this->institution->code
        ]),
        [
          'datetime' => '2026-08-29 17:00:00',
          'type' => AttendanceType::Out->value,
          'remark' => 'End of day'
        ]
      )
      ->assertOk()
      ->assertJsonPath('status', 'recorded');

    $attendance->refresh();

    expect($attendance->institution_staff_user_id)
      ->toBe($institutionUser->id)
      ->and($attendance->remark)
      ->toBe('Morning check-in End of day')
      ->and($attendance->signed_out_at->toDateTimeString())
      ->toBe('2026-08-29 17:00:00');
  }
);

it(
  'does not allow a non-staff institution user to use the staff self attendance endpoint',
  function () {
    $institutionUser = InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->state(['role' => InstitutionUserType::Student->value])
      ->create();

    actingAs($institutionUser->user, 'sanctum')
      ->postJson(
        route('api.institutions.attendance.self', [
          'institution' => $this->institution->code
        ])
      )
      ->assertForbidden();
  }
);

it(
  'uses the current server date and time when no attendance datetime is supplied',
  function () {
    Carbon::setTestNow(Carbon::parse('2026-08-29 23:59:58'));
    $institutionUser = InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->admin()
      ->create();

    actingAs($institutionUser->user, 'sanctum')
      ->postJson(
        route('api.institutions.attendance.self', [
          'institution' => $this->institution->code
        ]),
        [
          'type' => AttendanceType::In->value,
          'reference' => Str::orderedUuid()->toString()
        ]
      )
      ->assertOk();

    $attendance = Attendance::query()
      ->where('institution_user_id', $institutionUser->id)
      ->firstOrFail();

    expect($attendance->signed_in_at->toDateTimeString())->toBe(
      '2026-08-29 23:59:58'
    );
  }
);

it('validates the optional attendance datetime value', function () {
  $institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  actingAs($institutionUser->user, 'sanctum')
    ->postJson(
      route('api.institutions.attendance.self', [
        'institution' => $this->institution->code
      ]),
      [
        'datetime' => 'not a datetime',
        'type' => AttendanceType::In->value,
        'reference' => Str::orderedUuid()->toString()
      ]
    )
    ->assertUnprocessable()
    ->assertJsonValidationErrors(['datetime']);
});

it('requires authentication for self attendance', function () {
  postJson(
    route('api.institutions.attendance.self', [
      'institution' => $this->institution->code
    ])
  )->assertUnauthorized();
});
