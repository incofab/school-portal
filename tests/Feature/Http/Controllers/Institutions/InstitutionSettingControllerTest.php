<?php

use App\Enums\InstitutionSettingType;
use App\Enums\ResultExamMode;
use App\Enums\ResultSettingType;
use App\Enums\UserFullNameFormat;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
});

it('stores institution user full name display format', function () {
    actingAs($this->admin)
        ->postJson(route('institutions.settings.store', $this->institution), [
            'key' => InstitutionSettingType::UserFullNameFormat->value,
            'value' => UserFullNameFormat::LastFirstOther->value,
        ])
        ->assertOk();

    assertDatabaseHas('institution_settings', [
        'institution_id' => $this->institution->id,
        'key' => InstitutionSettingType::UserFullNameFormat->value,
        'value' => UserFullNameFormat::LastFirstOther->value,
    ]);
});

it('stores institution result exam display mode', function () {
    actingAs($this->admin)
        ->postJson(route('institutions.settings.store', $this->institution), [
            'key' => InstitutionSettingType::Result->value,
            'value' => [
                ResultSettingType::ExamMode->value => ResultExamMode::MidTerm->value,
            ],
            'type' => 'array',
        ])
        ->assertOk();

    $setting = InstitutionSetting::query()
        ->where('institution_id', $this->institution->id)
        ->where('key', InstitutionSettingType::Result->value)
        ->first();

    expect(json_decode($setting->value, true))->toMatchArray([
        ResultSettingType::ExamMode->value => ResultExamMode::MidTerm->value,
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
                'last_name' => 'Bello',
            ]);

        actingAs($this->admin)
            ->getJson(
                route('institutions.users.search', [
                    $this->institution,
                    'search' => 'Amina',
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
        'last_name' => 'Bello',
    ]);

    expect($user->full_name)->toBe('Amina Zainab Bello');
});
