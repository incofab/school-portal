<?php

use App\Models\AcademicSession;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CertificateTemplateSeeder;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
    $this->classification = Classification::factory()
        ->withInstitution($this->institution)
        ->create();
    $this->session = AcademicSession::factory()->create();
    app(CertificateTemplateSeeder::class)->run();
    $this->template = CertificateTemplate::query()
        ->where('slug', 'classic')
        ->firstOrFail();
    $this->customization = CertificateType::create([
        'institution_id' => $this->institution->id,
        'certificate_template_id' => $this->template->id,
        'name' => 'Classic School Awards',
        'institution_name' => $this->institution->name,
        'primary_color' => '#1f6b50',
        'custom_values' => [],
    ]);
    $this->route = fn (string $name, array $params = []) => route(
        'institutions.'.$name,
        [$this->institution->uuid, ...$params]
    );
});

function certificatePayload(array $overrides = []): array
{
    return array_merge(
        [
            'certificate_type_id' => null,
            'institution_user_ids' => [],
            'citation' => 'For successfully completing the programme.',
            'issued_on' => '2026-10-14',
            'academic_session_id' => null,
            'classification_id' => null,
            'signatory_name' => 'Ada Principal',
            'signatory_title' => 'Principal',
        ],
        $overrides
    );
}

it(
    'shows only certificate types and leaves global designs available to select',
    function () {
        actingAs($this->admin)
            ->get(($this->route)('certificate-types.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('institutions/certificates/manage-certificate-types')
                    ->has('certificateTypes', 1)
                    ->where('certificateTypes.0.template.slug', 'classic')
            );

        expect(CertificateTemplate::query()->count())->toBe(9);
        expect($this->template->template_path)->toContain('template-1.tsx');
        expect(
            CertificateTemplate::query()
                ->pluck('template_path')
                ->unique()
                ->count()
        )->toBe(9);
        expect(
            Schema::hasTable('certificate_types')
        )->toBeTrue();
        expect(
            Schema::hasTable('institution_certificate_templates')
        )->toBeFalse();
        expect(
            Schema::hasColumn('certificates', 'certificate_type_id')
        )->toBeTrue();
        expect(
            Schema::hasColumn('certificates', 'certificate_template_id')
        )->toBeFalse();
        expect(
            Schema::hasColumn('certificates', 'primary_color')
        )->toBeFalse();
        expect(
            Schema::hasColumn('certificates', 'accent_color')
        )->toBeFalse();
        expect(Schema::hasColumn('certificates', 'award_type'))->toBeFalse();
        expect(Schema::hasColumn('certificates', 'title'))->toBeFalse();
        expect(Schema::hasColumn('certificate_batches', 'award_type'))->toBeFalse();
        expect(Schema::hasColumn('certificate_batches', 'title'))->toBeFalse();
        expect(
            CertificateType::query()
                ->where('institution_id', $this->institution->id)
                ->count()
        )->toBe(1);
    }
);

it(
    'customizes an institution copy of a design without changing the global template',
    function () {
        $this->institution->update(['subtitle' => 'Learning with purpose']);

        actingAs($this->admin)
            ->put(
                ($this->route)('certificate-types.update', [$this->customization]),
                [
                    'name' => 'School of Excellence awards',
                    'certificate_template_id' => $this->template->id,
                    'institution_name' => 'School of Excellence',
                    'logo_url' => '',
                    'primary_color' => '#123456',
                    'is_active' => true,
                    'custom_values' => [
                        'school_motto' => 'Knowledge and service',
                        'custom_footer' => 'Accredited learning',
                    ],
                ]
            )
            ->assertOk();

        expect($this->customization->fresh()->institution_name)->toBe(
            $this->institution->name
        );
        expect($this->customization->fresh()->primary_color)->toBe('#123456');
        expect(
            $this->customization->fresh()->custom_values['school_motto']
        )->toBe('Learning with purpose');
        expect(
            $this->customization->fresh()->custom_values['custom_footer']
        )->toBe('Accredited learning');
        expect($this->template->fresh()->name)->toBe('Classic Honour');
    }
);

it('allows multiple institution templates to use one shared design', function () {
    actingAs($this->admin)
        ->post(($this->route)('certificate-types.store'), [
            'certificate_template_id' => $this->template->id,
            'name' => 'Blue academic awards',
            'institution_name' => $this->institution->name,
            'logo_url' => '',
            'primary_color' => '#2458a6',
            'custom_values' => [],
            'is_active' => true,
        ])
        ->assertOk();

    expect(
        CertificateType::query()
            ->where('institution_id', $this->institution->id)
            ->where('certificate_template_id', $this->template->id)
            ->count()
    )->toBe(2);
});

it(
    'issues one certificate through the selected certificate type',
    function () {
        $student = Student::factory()
            ->withInstitution($this->institution, $this->classification)
            ->create();

        actingAs($this->admin)
            ->post(
                ($this->route)('certificates.store'),
                certificatePayload([
                    'certificate_type_id' => $this->customization->id,
                    'institution_user_ids' => [$student->institution_user_id],
                    'academic_session_id' => $this->session->id,
                    'classification_id' => $this->classification->id,
                ])
            )
            ->assertOk();

        $certificate = Certificate::query()->firstOrFail();
        expect($certificate->certificate_batch_id)->toBeNull();
        expect($certificate->render_data['recipient_name'])->not->toBeEmpty();
        expect($certificate->render_data['template_path'])->toContain('template-1.tsx');
        expect($certificate->certificateType->template->id)->toBe(
            $this->template->id
        );
        assertDatabaseHas('certificates', [
            'institution_id' => $this->institution->id,
            'institution_user_id' => $student->institution_user_id,
            'certificate_type_id' => $this->customization->id,
            'academic_session_id' => $this->session->id,
        ]);
    }
);

it(
    'creates a batch separately and links each recipient issuance to it',
    function () {
        $batchData = [
            'name' => 'Graduation 2025/26',
            'certificate_type_id' => $this->customization->id,
            'citation' => 'For completing the programme.',
            'issued_on' => '2026-10-14',
            'academic_session_id' => $this->session->id,
            'classification_id' => null,
            'signatory_name' => 'Principal',
            'signatory_title' => 'School Principal',
        ];
        actingAs($this->admin)
            ->post(($this->route)('certificates.batches.store'), $batchData)
            ->assertOk();
        $batch = CertificateBatch::query()->firstOrFail();
        expect($batch->status)->toBe('open');

        $students = collect(range(1, 2))->map(
            fn () => Student::factory()
                ->withInstitution($this->institution, $this->classification)
                ->create()
        );
        actingAs($this->admin)
            ->post(($this->route)('certificates.store'), [
                'certificate_batch_id' => $batch->id,
                'institution_user_ids' => $students->pluck('institution_user_id')->all(),
            ])
            ->assertOk();

        expect(Certificate::query()->count())->toBe(2);
        expect(
            Certificate::query()
                ->pluck('certificate_batch_id')
                ->unique()
                ->all()
        )->toBe([$batch->id]);
        expect(Certificate::query()->first()->certificateType->name)->toBe(
            $this->customization->name
        );
    }
);

it(
    'rejects recipients and customizations from another institution',
    function () {
        $otherInstitution = Institution::factory()->create();
        $otherClass = Classification::factory()
            ->withInstitution($otherInstitution)
            ->create();
        $otherStudent = Student::factory()
            ->withInstitution($otherInstitution, $otherClass)
            ->create();

        actingAs($this->admin)
            ->post(
                ($this->route)('certificates.store'),
                certificatePayload([
                    'certificate_type_id' => $this->customization->id,
                    'institution_user_ids' => [$otherStudent->institution_user_id],
                ])
            )
            ->assertSessionHasErrors('institution_user_ids.0');

        $foreignCustomization = CertificateType::create([
            'institution_id' => $otherInstitution->id,
            'certificate_template_id' => $this->template->id,
            'name' => 'Other school classic awards',
            'institution_name' => $otherInstitution->name,
        ]);
        actingAs($this->admin)
            ->post(
                ($this->route)('certificates.store'),
                certificatePayload([
                    'certificate_type_id' => $foreignCustomization->id,
                    'institution_user_ids' => [$otherStudent->institution_user_id],
                ])
            )
            ->assertSessionHasErrors('certificate_type_id');
    }
);

it(
    'lets only the recipient or an administrator view an issued certificate',
    function () {
        $student = Student::factory()
            ->withInstitution($this->institution, $this->classification)
            ->create();
        $certificate = Certificate::create([
            'institution_id' => $this->institution->id,
            'certificate_type_id' => $this->customization->id,
            'institution_user_id' => $student->institution_user_id,
            'issued_by_institution_user_id' => $this->institution->createdBy
                ->institutionUsers()
                ->firstOrFail()->id,
            'reference_code' => 'CERT-RECIPIENT01',
            'issued_on' => '2026-10-14',
        ]);
        $teacher = User::factory()
            ->teacher($this->institution)
            ->create();

        actingAs($student->user)
            ->get(($this->route)('my-certificates.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('institutions/certificates/my-certificates')
                    ->has('certificates.data', 1)
            );
        actingAs($student->user)
            ->get(($this->route)('my-certificates.show', [$certificate]))
            ->assertOk();
        actingAs($teacher)
            ->get(($this->route)('certificates.show', [$certificate]))
            ->assertForbidden();
        actingAs($this->admin)
            ->get(($this->route)('certificates.show', [$certificate]))
            ->assertOk();
    }
);

it(
    'allows an administrator to revoke an issuance and prevents repeat revocation',
    function () {
        $student = Student::factory()
            ->withInstitution($this->institution, $this->classification)
            ->create();
        $certificate = Certificate::create([
            'institution_id' => $this->institution->id,
            'certificate_type_id' => $this->customization->id,
            'institution_user_id' => $student->institution_user_id,
            'issued_by_institution_user_id' => $this->institution->createdBy
                ->institutionUsers()
                ->firstOrFail()->id,
            'reference_code' => 'CERT-REVOKE0001',
            'issued_on' => '2026-10-14',
        ]);

        actingAs($this->admin)
            ->post(($this->route)('certificates.revoke', [$certificate]), [
                'revocation_reason' => 'Award was recorded in error.',
            ])
            ->assertOk();
        expect($certificate->fresh()->status)->toBe('revoked');
        actingAs($this->admin)
            ->post(($this->route)('certificates.revoke', [$certificate]), [
                'revocation_reason' => 'Second attempt.',
            ])
            ->assertUnprocessable();
    }
);

it(
    'limits certificate administration to institution administrators',
    function () {
        $teacher = User::factory()
            ->teacher($this->institution)
            ->create();
        actingAs($teacher)
            ->get(($this->route)('certificates.index'))
            ->assertForbidden();
        actingAs($teacher)
            ->get(($this->route)('certificates.create'))
            ->assertForbidden();
        actingAs($teacher)
            ->get(($this->route)('certificate-types.index'))
            ->assertForbidden();
        actingAs($teacher)
            ->get(($this->route)('certificates.batches'))
            ->assertForbidden();
    }
);
