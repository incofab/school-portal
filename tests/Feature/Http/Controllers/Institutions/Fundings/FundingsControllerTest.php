<?php

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\PaymentReference;
use Illuminate\Support\Facades\Http;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentMerchantType;
use function Pest\Laravel\{actingAs, postJson};
use Illuminate\Testing\Fluent\AssertableJson;

/**
 * ./vendor/bin/pest --filter FundingsControllerTest
 */

beforeEach(function () {
  // Setup the mock user and institution
  // $this->user = User::factory()->create();  // Assuming you're using a factory for User
  $this->institution = Institution::factory()->create(); // Same for Institution
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->admin = $this->institution->createdBy;
  $this->user = $this->institutionUser->user;
});

it(
  'creates a funding with Paystack and returns the correct response',
  function () {
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

    // Define the request data
    $data = [
      'amount' => 1000,
      'reference' => $reference,
      'merchant' => PaymentMerchantType::Paystack->value
    ];

    // Perform the post request to store the funding
    actingAs($this->admin)
      ->postJson(
        route('institutions.fundings.store', $this->institution),
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
      'purpose' => PaymentPurpose::WalletFunding->value,
      'merchant' => PaymentMerchantType::Paystack->value
    ]);
  }
);

it('fails validation when required fields are missing', function () {
  // Perform the post request with missing data
  actingAs($this->admin)
    ->postJson(route('institutions.fundings.store', $this->institution), [])
    ->assertStatus(422) // Expect a validation error
    ->assertJsonValidationErrors(['amount', 'reference']);
});

it('fails if reference is not unique', function () {
  // Create an existing payment reference
  PaymentReference::factory()->create([
    'reference' => 'existing-reference-1234'
  ]);

  // Perform the post request with a non-unique reference
  actingAs($this->admin)
    ->postJson(route('institutions.fundings.store', $this->institution), [
      'amount' => 1000,
      'reference' => 'existing-reference-1234'
    ])
    ->assertStatus(422) // Expect a validation error due to unique constraint violation
    ->assertJsonValidationErrors(['reference']);
});
