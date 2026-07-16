<?php

use App\Enums\InstitutionSettingType;
use App\Enums\ResultExamMode;
use App\Enums\ResultSettingType;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\TermDetail;
use App\Models\User;
use App\Support\SettingsHandler;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->instAdmin = $this->institution->createdBy;
    $this->getRoute = route('institutions.term-details.index', [
        'institution' => $this->institution->uuid,
    ]);
    $this->createRoute = route('institutions.term-details.create', [
        'institution' => $this->institution->uuid,
    ]);
    SettingsHandler::clear();
    $this->seedSetting = function () {
        InstitutionSetting::factory()
            ->term($this->institution)
            ->create();
        InstitutionSetting::factory()
            ->academicSession($this->institution)
            ->create();
    };
    $this->createTermDetail = function () {
        $this->termDetail = TermDetail::factory()
            ->for($this->institution)
            ->create();
        $this->updateRoute = route('institutions.term-details.update', [
            $this->institution->uuid,
            $this->termDetail->id,
        ]);
    };
});

it('renders the term details page with expected data', function () {
    actingAs($this->instAdmin)
        ->get($this->getRoute)
        ->assertStatus(401);
    ($this->seedSetting)();
    $studentUser = User::factory()
        ->student($this->institution)
        ->create();
    actingAs($studentUser)
        ->get($this->getRoute)
        ->assertForbidden();

    actingAs($this->instAdmin)
        ->get($this->getRoute)
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('institutions/term-details/list-term-details')
                ->has('termDetails')
        );
});

it('opens a create page for the current term detail with saturday and sunday inactive by default', function () {
    ($this->seedSetting)();

    actingAs($this->instAdmin)
        ->get($this->createRoute)
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('institutions/term-details/create-edit-term-detail')
                ->where('termDetail.inactive_weekdays', [5, 6])
                ->where('mode', 'create')
        );

    SettingsHandler::clear();

    actingAs($this->instAdmin)
        ->get($this->getRoute)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('termDetail'));

    $this->assertDatabaseHas('term_details', [
        'institution_id' => $this->institution->id,
    ]);
});

it('opens the term detail edit page for an existing record', function () {
    ($this->createTermDetail)();

    $editRoute = route('institutions.term-details.edit', [
        $this->institution->uuid,
        $this->termDetail->id,
    ]);

    actingAs($this->instAdmin)
        ->get($editRoute)
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('institutions/term-details/create-edit-term-detail')
                ->where('termDetail.id', $this->termDetail->id)
                ->where('mode', 'edit')
        );
});

it('updates a term detail with valid data', function () {
    ($this->createTermDetail)();
    $payload = [
        'expected_attendance_count' => 25,
        'start_date' => now()
            ->subDays(5)
            ->toDateString(),
        'end_date' => now()->toDateString(),
        'next_term_resumption_date' => now()
            ->addWeek()
            ->toDateString(),
        'inactive_weekdays' => [5, 6],
        'special_active_days' => [
            [
                'date' => now()
                    ->addDays(2)
                    ->toDateString(),
                'reason' => 'Weekend class',
            ],
        ],
        'inactive_days' => [
            [
                'date' => now()
                    ->addDays(3)
                    ->toDateString(),
                'reason' => 'Public holiday',
            ],
            [
                'date' => now()
                    ->addDays(4)
                    ->toDateString(),
                'reason' => 'Staff training',
            ],
        ],
        'result_exam_mode' => ResultExamMode::FullTerm->value,
    ];

    actingAs($this->instAdmin)
        ->putJson($this->updateRoute, $payload)
        ->assertOk();
    // dd($this->termDetail->fresh()->toArray());
    expect($this->termDetail->refresh())
        ->expected_attendance_count->toBe(25)
        ->start_date->toDateString()
        ->toBe($payload['start_date'])
        ->end_date->toDateString()
        ->toBe($payload['end_date'])
        ->next_term_resumption_date->toDateString()
        ->toBe($payload['next_term_resumption_date'])
        ->inactive_weekdays->toMatchArray($payload['inactive_weekdays'])
        ->special_active_days->toBe($payload['special_active_days'])
        ->inactive_days->toBe($payload['inactive_days'])
        ->result_exam_mode->toBe(ResultExamMode::FullTerm);
});

it('validates update term detail payload', function () {
    ($this->createTermDetail)();
    $payload = [
        'expected_attendance_count' => 'not-an-integer',
        'start_date' => 'invalid-date',
        'end_date' => 'invalid-date',
        'next_term_resumption_date' => 'not-a-date',
        'inactive_weekdays' => ['sun'],
        'special_active_days' => [
            [
                'date' => 'not-a-date',
            ],
        ],
        'inactive_days' => [
            [
                'reason' => '',
            ],
        ],
        'result_exam_mode' => 'invalid-mode',
    ];
    actingAs($this->instAdmin)
        ->putJson($this->updateRoute, $payload)
        ->assertJsonValidationErrors([
            'expected_attendance_count',
            'start_date',
            'end_date',
            'next_term_resumption_date',
            'inactive_weekdays.0',
            'special_active_days.0.date',
            'special_active_days.0.reason',
            'inactive_days.0.date',
            'inactive_days.0.reason',
            'result_exam_mode',
        ]);
});

it('lets term detail exam mode override institution result exam mode', function () {
    InstitutionSetting::factory()
        ->for($this->institution)
        ->create([
            'key' => InstitutionSettingType::Result->value,
            'value' => json_encode([
                ResultSettingType::ExamMode->value => ResultExamMode::MidTerm->value,
            ]),
            'type' => 'array',
        ]);

    $termDetail = TermDetail::factory()
        ->for($this->institution)
        ->create(['result_exam_mode' => ResultExamMode::None->value]);

    $settings = SettingsHandler::makeFromInstitution($this->institution->fresh());

    expect($settings->shouldDisplayExamResults($termDetail, true))->toBeFalse()
        ->and($settings->shouldDisplayExamResults(null, true))->toBeTrue()
        ->and($settings->shouldDisplayExamResults(null, false))->toBeFalse();
});
