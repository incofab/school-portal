<?php

use App\Models\AdmissionApplication;
use App\Models\ApplicationGuardian;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;

use App\Models\Classification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
  Storage::fake('s3_public');
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

it('tests the index page', function () {
  $route = route('institutions.admission-applications.index', [
    'institution' => $this->institution->uuid
  ]);

  AdmissionApplication::factory(5)
    ->for($this->institution)
    ->create();

  $ordinaryUser = User::factory()
    ->admin()
    ->create();

  actingAs($ordinaryUser)
    ->getJson($route)
    ->assertForbidden();

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert
        ->has('admissionApplications.data', 5)
        ->component('institutions/admissions/list-admission-applications')
    );
});

it('store admission application data', function () {
  $route = route('institutions.admissions.store', [
    'institution' => $this->institution->uuid
  ]);

  postJson($route, [])->assertJsonValidationErrors([
    'reference',
    'first_name',
    'last_name',
    'guardians'
  ]);

  $admissionApplicationData = AdmissionApplication::factory()
    ->for($this->institution)
    ->make()
    ->toArray();

  $guardians = ApplicationGuardian::factory(2)
    ->make(['admission_application_id' => null])
    ->toArray();

  $data = [
    ...$admissionApplicationData,
    'guardians' => $guardians,
    'photo' => UploadedFile::fake()->image('dummy-photo.jpg')
  ];
  // dd(['data' => $data, 'institution' => $this->institution->toArray()]);
  postJson($route, $data)->assertOk();
  postJson($route, $data)->assertJsonValidationErrorFor('reference');

  $admissionApplication = AdmissionApplication::where(
    'reference',
    $admissionApplicationData['reference']
  )->first();
  assertDatabaseCount('admission_applications', 1);
  assertDatabaseHas(
    'admission_applications',
    collect($admissionApplicationData)
      ->except('photo')
      ->toArray()
  );
  assertNotNull($admissionApplication->photo);

  assertDatabaseCount('application_guardians', 2);
  foreach ($guardians as $key => $guardian) {
    assertDatabaseHas(
      'application_guardians',
      collect($guardian)
        ->except('admission_application_id')
        ->toArray()
    );
  }
});

it('will not run if admission status is not pending', function () {
  $admissionApplication = AdmissionApplication::factory()
    ->for($this->institution)
    ->create(['admission_status' => 'declined']);

  $route = route('institutions.admission-applications.update-status', [
    $this->institution->uuid,
    $admissionApplication->id
  ]);

  actingAs($this->admin)
    ->postJson($route, [])
    ->assertStatus(401);
});

it('handles admission and updates admission status', function () {
  $admissionApplication = AdmissionApplication::factory()
    ->for($this->institution)
    ->create();
  // dd($admissionApplication->fresh()->toArray());

  $route = route('institutions.admission-applications.update-status', [
    $this->institution->uuid,
    $admissionApplication->id
  ]);

  $classification = Classification::factory()
    ->for($this->institution)
    ->create();

  $data = [
    'admission_status' => 'admitted',
    'classification' => $classification->id
  ];

  actingAs($this->admin)
    ->postJson($route, $data)
    ->assertOk();

  // expect($admissionApplication->fresh()->admission_status)->toBe('admitted');
  // expect($admissionApplication->fresh())->admission_status->toBe('admitted')->id->toBe(1)->;
  assertEquals($admissionApplication->fresh()->admission_status, 'admitted');
  $user = User::where([
    'first_name' => $admissionApplication->first_name,
    'last_name' => $admissionApplication->last_name
  ])->first();
  assertNotNull($user);
  assertDatabaseHas('students', [
    'classification_id' => $classification->id,
    'user_id' => $user->id
  ]);

  $guardian = $admissionApplication->applicationGuardians()->first();
  $guardianUser = User::where([
    'email' => $guardian->email
  ])->first();
  assertCount(1, $guardianUser->guardianStudents()->get());
});
