<?php

use Illuminate\Support\Facades\Http;
use function Pest\Laravel\postJson;

beforeEach(function () {
  // Fake Monnify auth API call
  Http::fake([
    'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
      'requestSuccessful' => true,
      'responseBody' => [
        'accessToken' => 'mock_token'
      ]
    ])
  ]);

  config([
    'services.monnify.public' => 'mock_public_key',
    'services.monnify.secret' => 'mock_secret_key'
  ]);
});

it('validates required fields', function () {
  postJson(route('bank-accounts.validate'), [])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['bank_code', 'account_number']);
});

it('returns 200 and account details on successful validation', function () {
  Http::fake([
    'sandbox.monnify.com/api/v1/disbursements/account/validate*' => Http::response(
      [
        'requestSuccessful' => true,
        'responseBody' => [
          'accountNumber' => '1234567890',
          'accountName' => 'John Doe',
          'bankCode' => '123'
        ]
      ],
      200
    )
  ]);

  $payload = [
    'bank_code' => '123',
    'account_number' => '1234567890'
  ];

  postJson(route('bank-accounts.validate'), $payload)
    ->assertStatus(200)
    ->assertJson([
      'message' => 'Banks recorded',
      'account_number' => '1234567890',
      'account_name' => 'John Doe',
      'bank_code' => '123'
    ]);
});

it('returns 403 when Monnify validation fails', function () {
  Http::fake([
    'sandbox.monnify.com/api/v1/disbursements/account/validate*' => Http::response(
      [
        'requestSuccessful' => false,
        'responseMessage' => 'Invalid account details'
      ],
      200
    )
  ]);

  $payload = [
    'bank_code' => '123',
    'account_number' => '1234567890'
  ];

  postJson(route('bank-accounts.validate'), $payload)
    ->assertStatus(403)
    ->assertJson([
      'success' => false,
      'message' => 'Invalid account details'
    ]);
});
