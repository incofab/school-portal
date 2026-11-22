<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Models\ReservedAccount;
use App\Models\User;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
  // Ensure the route exists for the test
  $this->secret = 'test_secret';
  Config::set('services.payment-point.secret', $this->secret);
  Config::set('app.debug', false);
});

test('it processes a valid payment point webhook', function () {
  $user = User::factory()->create(['wallet' => 0]);
  ReservedAccount::factory()->create([
    'merchant' => PaymentMerchantType::PaymentPoint,
    'bank_name' => 'Test Bank',
    'account_number' => '1234567890',
    'reservable_type' => $user->getMorphClass(),
    'reservable_id' => $user->id
  ]);

  $settlementAmount = 4800;
  $payload = [
    'transaction_id' => 'TX123456',
    'amount_paid' => 5000,
    'settlement_amount' => $settlementAmount,
    'transaction_status' => 'success',
    'receiver' => [
      'bank' => 'Test Bank',
      'account_number' => '1234567890'
    ]
  ];

  $jsonPayload = json_encode($payload);

  $signature = hash_hmac('sha256', $jsonPayload, $this->secret);

  $this->postJson(route('api.payment-point.webhook'), $payload, [
    'PAYMENTPOINT_SIGNATURE' => $signature
  ])->assertOk();

  $user->refresh();
  expect($user->wallet)->toBe(floatval($settlementAmount));
});

test(
  'it does not duplicate reserved-account credits for duplicate payment point webhooks',
  function () {
    $user = User::factory()->create(['wallet' => 0]);
    ReservedAccount::factory()->create([
      'merchant' => PaymentMerchantType::PaymentPoint,
      'bank_name' => 'Test Bank',
      'account_number' => '1234567890',
      'reservable_type' => $user->getMorphClass(),
      'reservable_id' => $user->id
    ]);

    $settlementAmount = 4800;
    $payload = [
      'transaction_id' => 'TX-DUPLICATE',
      'amount_paid' => 5000,
      'settlement_amount' => $settlementAmount,
      'transaction_status' => 'success',
      'receiver' => [
        'bank' => 'Test Bank',
        'account_number' => '1234567890'
      ]
    ];

    $jsonPayload = json_encode($payload);
    $signature = hash_hmac('sha256', $jsonPayload, $this->secret);

    $this->postJson(route('api.payment-point.webhook'), $payload, [
      'PAYMENTPOINT_SIGNATURE' => $signature
    ])->assertOk();
    $this->postJson(route('api.payment-point.webhook'), $payload, [
      'PAYMENTPOINT_SIGNATURE' => $signature
    ])->assertOk();

    expect($user->fresh()->wallet)->toBe(floatval($settlementAmount));
    expect(
      UserTransaction::query()
        ->where('reference', 'TX-DUPLICATE')
        ->count()
    )->toBe(1);
  }
);

test(
  'it rejects a payment point webhook with no signature without throwing',
  function () {
    $this->postJson(route('api.payment-point.webhook'), [
      'transaction_id' => 'TX-MISSING-SIGNATURE',
      'amount_paid' => 5000,
      'settlement_amount' => 4800,
      'transaction_status' => 'success'
    ])
      ->assertBadRequest()
      ->assertSee('Missing signature.');

    expect(
      UserTransaction::query()
        ->where('reference', 'TX-MISSING-SIGNATURE')
        ->exists()
    )->toBeFalse();
  }
);
