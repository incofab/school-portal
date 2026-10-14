<?php

use App\Models\CertificateTemplate;
use App\Models\User;
use Database\Seeders\CertificateTemplateSeeder;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->manager = User::factory()->adminManager()->create();
    app(CertificateTemplateSeeder::class)->run();
});

it('lists seeded global designs in the manager catalog', function () {
    actingAs($this->manager)
        ->get(route('managers.certificate-templates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('managers/certificate-templates/list-certificate-templates')
            ->has('templates', 9)
            ->where('templates.0.slug', 'celebration'));
});

it('lets a manager admin create and update a global design', function () {
    $payload = [
        'slug' => 'formal-award',
        'name' => 'Formal Award',
        'template_path' => 'resources/js/pages/institutions/certificates/templates/template-1.tsx',
        'description' => 'A formal certificate layout.',
        'is_active' => true,
    ];

    actingAs($this->manager)
        ->postJson(route('managers.certificate-templates.store'), $payload)
        ->assertOk();

    $template = CertificateTemplate::query()->where('slug', 'formal-award')->firstOrFail();
    expect($template->placeholders)->not->toBeEmpty();

    actingAs($this->manager)
        ->putJson(route('managers.certificate-templates.update', [$template]), [
            ...$payload,
            'name' => 'Formal Achievement',
            'is_active' => false,
        ])
        ->assertOk();

    expect($template->fresh()->name)->toBe('Formal Achievement');
    expect($template->fresh()->is_active)->toBeFalse();
});

it('blocks partners from managing global certificate designs', function () {
    $partner = User::factory()->partnerManager()->create();
    actingAs($partner)
        ->get(route('managers.certificate-templates.index'))
        ->assertRedirect();
});
