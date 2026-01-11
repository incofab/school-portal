<?php

use App\Enums\AdmissionStatusType;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\ApplicationGuardian;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;

use App\Models\Classification;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

beforeEach(function () {
  Storage::fake('s3_public');
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->admissionForm = AdmissionForm::factory()
    ->for($this->institution)
    ->create();
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

  postJson($route, [
    'admission_form_id' => $this->admissionForm->id
  ])->assertJsonValidationErrors(['reference', 'first_name', 'last_name']);

  $admissionApplicationData = AdmissionApplication::factory()
    ->admissionForm($this->admissionForm)
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
      ->except('photo', 'name', 'photo_url')
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
    ->admissionForm($this->admissionForm)
    ->create(['admission_status' => AdmissionStatusType::Declined->value]);

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
    ->admissionForm($this->admissionForm)
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
    'admission_status' => AdmissionStatusType::Admitted->value,
    'classification' => $classification->id
  ];

  actingAs($this->admin)
    ->postJson($route, $data)
    ->assertOk();

  assertEquals(
    $admissionApplication->fresh()->admission_status,
    AdmissionStatusType::Admitted
  );
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

it(
  'can buy an admission form and initialize payment with paystack',
  function () {
    $admissionApplication = AdmissionApplication::factory()
      ->admissionForm($this->admissionForm)
      ->create();
    $reference = 'unique-reference-1234';
    // Simulate the response from Paystack
    Http::fake([
      'https://api.paystack.co/transaction/initialize' => Http::response(
        [
          'status' => true,
          'data' => [
            'authorization_url' =>
              'https://paystack.com/checkout/authorization_url',
            'reference' => $reference,
            'access_code' => 'access_code_1234'
          ]
        ],
        200
      )
    ]);
    $data = [
      'reference' => $reference,
      'merchant' => PaymentMerchantType::Paystack->value
    ];
    postJson(
      route('institutions.admission-forms.buy', [
        $this->institution->uuid,
        $this->admissionForm->id,
        $admissionApplication->id
      ]),
      $data
    )
      ->assertStatus(200)
      ->assertJson(
        fn(AssertableJson $json) => $json
          ->has('authorization_url')
          ->where('reference', $reference)
          ->etc()
      );

    // Check if the PaymentReference was created in the database
    $this->assertDatabaseHas('payment_references', [
      'reference' => $data['reference'],
      'purpose' => PaymentPurpose::AdmissionFormPurchase->value,
      'merchant' => PaymentMerchantType::Paystack->value,
      'amount' => $this->admissionForm->price,
      'paymentable_id' => $admissionApplication->id,
      'paymentable_type' => $admissionApplication->getMorphClass(),
      'payable_id' => $this->admissionForm->id,
      'payable_type' => $this->admissionForm->getMorphClass(),
      'institution_id' => $this->institution->id,
      'meta' => json_encode([
        'admission_application_id' => $admissionApplication->id
      ])
    ]);
  }
);
