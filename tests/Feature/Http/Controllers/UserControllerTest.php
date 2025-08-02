<?php

use App\Models\ReservedAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use function Pest\Laravel\{actingAs, assertDatabaseHas, putJson};

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

it('updates BVN or NIN and reserves account if not already set', function () {
  // Create user without BVN/NIN
  $user = User::factory()->create([
    'bvn' => null,
    'nin' => null
  ]);

  actingAs($user);

  // Fake Monnify reserve account API call
  Http::fake([
    'https://sandbox.monnify.com/api/v2/bank-transfer/reserved-accounts' => Http::response(
      [
        'requestSuccessful' => true,
        'responseBody' => [
          'accounts' => [
            [
              'accountName' => 'Test User ExamScholars',
              'accountNumber' => '1234567890',
              'bankCode' => '999',
              'bankName' => 'Mock Bank'
            ]
          ]
        ]
      ]
    )
  ]);

  $payload = [
    'type' => 'bvn',
    'value' => '12345678901'
  ];

  expect(ReservedAccount::all())->toBeEmpty();

  putJson(route('users.bvn-nin.update'), $payload)->assertOk();

  expect($user->fresh()->bvn)->toBe('12345678901');
  assertDatabaseHas('reserved_accounts', [
    'reservable_id' => $user->id,
    'reservable_type' => $user->getMorphClass()
  ]);
});

it('updates BVN or NIN without triggering Monnify if already set', function () {
  // User already has BVN
  $user = User::factory()->create([
    'bvn' => '11111111111',
    'nin' => null
  ]);

  actingAs($user);

  // Don't expect any external request here
  Http::fake();

  $payload = [
    'type' => 'bvn',
    'value' => '22222222222'
  ];

  putJson(route('users.bvn-nin.update'), $payload)->assertOk();

  expect($user->fresh()->bvn)->toBe('22222222222');
  expect(ReservedAccount::all())->toBeEmpty();
});

it(
  'reverts bvn/nin and returns 403 if Monnify account creation fails',
  function () {
    $user = User::factory()->create([
      'bvn' => null,
      'nin' => null
    ]);

    actingAs($user);

    Http::fake([
      'https://sandbox.monnify.com/api/v2/bank-transfer/reserved-accounts' => Http::response(
        [
          'requestSuccessful' => false,
          'responseMessage' => 'Failed to reserve account'
        ],
        200
      )
    ]);

    $payload = [
      'type' => 'nin',
      'value' => '98765432109'
    ];

    putJson(route('users.bvn-nin.update'), $payload)
      ->assertStatus(403)
      ->assertJsonStructure(['message']);

    expect($user->fresh()->nin)->toBeNull();
  }
);
