<?php

use App\Actions\SeedSetupData;
use App\Enums\AttendanceNotificationType;
use App\Enums\InstitutionSettingType;
use App\Enums\NotificationChannelsType;
use App\Enums\ResultExamMode;
use App\Enums\ResultSettingType;
use App\Enums\TermType;
use App\Enums\UserFullNameFormat;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

it('stores institution user full name display format', function () {
  actingAs($this->admin)
    ->postJson(route('institutions.settings.store', $this->institution), [
      'key' => InstitutionSettingType::UserFullNameFormat->value,
      'value' => UserFullNameFormat::LastFirstOther->value
    ])
    ->assertOk();

  assertDatabaseHas('institution_settings', [
    'institution_id' => $this->institution->id,
    'key' => InstitutionSettingType::UserFullNameFormat->value,
    'value' => UserFullNameFormat::LastFirstOther->value
  ]);
});

it(
  'stores attendance notification timing and preferred message option',
  function () {
    actingAs($this->admin)
      ->postJson(
        route('institutions.settings.store-multiple', $this->institution),
        [
          'settings' => [
            [
              'key' => InstitutionSettingType::AttendanceNotification->value,
              'value' => AttendanceNotificationType::CheckInAndOut->value
            ],
            [
              'key' => InstitutionSettingType::PreferredMessageOption->value,
              'value' => NotificationChannelsType::Whatsapp->value
            ]
          ]
        ]
      )
      ->assertOk();

    assertDatabaseHas('institution_settings', [
      'institution_id' => $this->institution->id,
      'key' => InstitutionSettingType::AttendanceNotification->value,
      'value' => AttendanceNotificationType::CheckInAndOut->value
    ]);
    assertDatabaseHas('institution_settings', [
      'institution_id' => $this->institution->id,
      'key' => InstitutionSettingType::PreferredMessageOption->value,
      'value' => NotificationChannelsType::Whatsapp->value
    ]);
  }
);

it(
  'seeds disabled attendance notifications and SMS as the default message option',
  function () {
    assertDatabaseHas('institution_settings', [
      'institution_id' => $this->institution->id,
      'key' => InstitutionSettingType::AttendanceNotification->value,
      'value' => AttendanceNotificationType::None->value
    ]);
    assertDatabaseHas('institution_settings', [
      'institution_id' => $this->institution->id,
      'key' => InstitutionSettingType::PreferredMessageOption->value,
      'value' => NotificationChannelsType::Sms->value
    ]);
  }
);

it('stores multiple institution settings together', function () {
  $academicSession = AcademicSession::factory()->create();

  actingAs($this->admin)
    ->postJson(
      route('institutions.settings.store-multiple', $this->institution),
      [
        'settings' => [
          [
            'key' => InstitutionSettingType::CurrentTerm->value,
            'value' => TermType::Second->value
          ],
          [
            'key' => InstitutionSettingType::CurrentAcademicSession->value,
            'value' => $academicSession->id
          ]
        ]
      ]
    )
    ->assertOk();

  assertDatabaseHas('institution_settings', [
    'institution_id' => $this->institution->id,
    'key' => InstitutionSettingType::CurrentTerm->value,
    'value' => TermType::Second->value
  ]);
  assertDatabaseHas('institution_settings', [
    'institution_id' => $this->institution->id,
    'key' => InstitutionSettingType::CurrentAcademicSession->value,
    'value' => (string) $academicSession->id
  ]);
});

it('stores result access, PIN usage, and presentation together', function () {
  actingAs($this->admin)
    ->postJson(
      route('institutions.settings.store-multiple', $this->institution),
      [
        'settings' => [
          [
            'key' => InstitutionSettingType::UsesMidTermResult->value,
            'value' => true
          ],
          [
            'key' => InstitutionSettingType::ResultActivationRequired->value,
            'value' => false
          ],
          [
            'key' => InstitutionSettingType::PinUsageCount->value,
            'value' => 3
          ],
          [
            'key' => InstitutionSettingType::Result->value,
            'value' => [
              ResultSettingType::ExamMode->value =>
                ResultExamMode::MidTerm->value,
              ResultSettingType::UseSessionResultAsThirdTerm->value => true
            ],
            'type' => 'array'
          ]
        ]
      ]
    )
    ->assertOk();

  assertDatabaseHas('institution_settings', [
    'institution_id' => $this->institution->id,
    'key' => InstitutionSettingType::PinUsageCount->value,
    'value' => '3'
  ]);

  $setting = InstitutionSetting::query()
    ->where('institution_id', $this->institution->id)
    ->where('key', InstitutionSettingType::Result->value)
    ->first();

  expect(json_decode($setting->value, true))->toMatchArray([
    ResultSettingType::ExamMode->value => ResultExamMode::MidTerm->value,
    ResultSettingType::UseSessionResultAsThirdTerm->value => true
  ]);
});

it(
  'seeds current academic session and first term settings when an institution is created',
  function () {
    AcademicSession::query()->forceDelete();
    AcademicSession::factory()->create([
      'order_index' => 30,
      'is_active' => false
    ]);
    $activeSession = AcademicSession::factory()
      ->active()
      ->create(['order_index' => 20]);

    $institution = Institution::factory()->create();

    assertDatabaseHas('institution_settings', [
      'institution_id' => $institution->id,
      'key' => InstitutionSettingType::CurrentAcademicSession->value,
      'value' => (string) $activeSession->id
    ]);
    assertDatabaseHas('institution_settings', [
      'institution_id' => $institution->id,
      'key' => InstitutionSettingType::CurrentTerm->value,
      'value' => TermType::First->value
    ]);
  }
);

it(
  'uses the latest academic session for new institution settings when none is active',
  function () {
    AcademicSession::query()->forceDelete();
    AcademicSession::factory()->create([
      'order_index' => 10,
      'is_active' => false
    ]);
    $latestSession = AcademicSession::factory()->create([
      'order_index' => 20,
      'is_active' => false
    ]);

    $institution = Institution::factory()->create();

    assertDatabaseHas('institution_settings', [
      'institution_id' => $institution->id,
      'key' => InstitutionSettingType::CurrentAcademicSession->value,
      'value' => (string) $latestSession->id
    ]);
  }
);

it(
  'does not replace existing institution academic settings when setup data is rerun',
  function () {
    $activeSession = AcademicSession::factory()
      ->active()
      ->create(['order_index' => 20]);

    InstitutionSetting::query()
      ->where('institution_id', $this->institution->id)
      ->where('key', InstitutionSettingType::CurrentTerm->value)
      ->update(['value' => TermType::Second->value]);

    SeedSetupData::run($this->institution);

    assertDatabaseHas('institution_settings', [
      'institution_id' => $this->institution->id,
      'key' => InstitutionSettingType::CurrentTerm->value,
      'value' => TermType::Second->value
    ]);
    assertDatabaseHas('institution_settings', [
      'institution_id' => $this->institution->id,
      'key' => InstitutionSettingType::CurrentAcademicSession->value,
      'value' => (string) $activeSession->id
    ]);
  }
);

it('stores institution result exam display mode', function () {
  actingAs($this->admin)
    ->postJson(route('institutions.settings.store', $this->institution), [
      'key' => InstitutionSettingType::Result->value,
      'value' => [
        ResultSettingType::ExamMode->value => ResultExamMode::MidTerm->value
      ],
      'type' => 'array'
    ])
    ->assertOk();

  $setting = InstitutionSetting::query()
    ->where('institution_id', $this->institution->id)
    ->where('key', InstitutionSettingType::Result->value)
    ->first();

  expect(json_decode($setting->value, true))->toMatchArray([
    ResultSettingType::ExamMode->value => ResultExamMode::MidTerm->value
  ]);
});

it('stores the session result as the third-term result setting', function () {
  actingAs($this->admin)
    ->postJson(route('institutions.settings.store', $this->institution), [
      'key' => InstitutionSettingType::Result->value,
      'value' => [
        ResultSettingType::UseSessionResultAsThirdTerm->value => true
      ],
      'type' => 'array'
    ])
    ->assertOk();

  $setting = InstitutionSetting::query()
    ->where('institution_id', $this->institution->id)
    ->where('key', InstitutionSettingType::Result->value)
    ->first();

  expect(json_decode($setting->value, true))->toMatchArray([
    ResultSettingType::UseSessionResultAsThirdTerm->value => true
  ]);
});

it(
  'uses the institution full name display format on institution-scoped user responses',
  function () {
    InstitutionSetting::factory()
      ->userFullNameFormat(
        $this->institution,
        UserFullNameFormat::LastFirstOther
      )
      ->create();

    $user = User::factory()
      ->teacher($this->institution)
      ->create([
        'first_name' => 'Amina',
        'other_names' => 'Zainab',
        'last_name' => 'Bello'
      ]);

    actingAs($this->admin)
      ->getJson(
        route('institutions.users.search', [
          $this->institution,
          'search' => 'Amina'
        ])
      )
      ->assertOk()
      ->assertJsonPath('result.data.0.user.full_name', 'Amina Zainab Bello');
  }
);

it('keeps the default full name order outside institution scope', function () {
  $user = User::factory()->make([
    'first_name' => 'Amina',
    'other_names' => 'Zainab',
    'last_name' => 'Bello'
  ]);

  expect($user->full_name)->toBe('Amina Zainab Bello');
});
