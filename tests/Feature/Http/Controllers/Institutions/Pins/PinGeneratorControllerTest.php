<?php

use App\Models\Institution;
use App\Models\PinGenerator;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->actingAs($this->admin);
});

it('can render the index page', function () {
  PinGenerator::factory(3)
    ->withInstitution($this->institution)
    ->create();

  getJson(route('institutions.pin-generators.index', $this->institution->uuid))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/pins/list-pin-generators')
        ->has('pinGenerators.data', 3)
    );
});

it('can render the create page', function () {
  getJson(route('institutions.pin-generators.create', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page->component(
        'institutions/pins/generate-pin'
      )
    );
});

it('can store a pin generator and associated pins', function () {
  $data = [
    'num_of_pins' => 5,
    'comment' => 'Test Comment',
    'reference' => 'TEST-REF-123'
  ];

  postJson(
    route('institutions.pin-generators.store', $this->institution->uuid),
    $data
  )->assertOk();

  $this->assertDatabaseHas('pin_generators', [
    ...$data,
    'institution_id' => $this->institution->id,
    'user_id' => $this->admin->id
  ]);

  $pinGenerator = PinGenerator::where('reference', $data['reference'])->first();
  $this->assertDatabaseCount('pins', $pinGenerator->num_of_pins);
  $this->assertDatabaseHas('pins', ['pin_generator_id' => $pinGenerator->id]);
});

it('can show the pins for a pin generator', function () {
  $pinGenerator = PinGenerator::factory()
    ->withInstitution($this->institution)
    ->pins()
    ->create();

  getJson(
    route('institutions.pin-generators.show', [
      $this->institution->uuid,
      $pinGenerator
    ])
  )
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/pins/display-pins')
        ->has('pins', $pinGenerator->num_of_pins)
        ->has('resultCheckerUrl')
    );
});
