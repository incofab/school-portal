<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Models\ReservedAccount;
use App\Models\User;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\withHeader;

beforeEach(function () {
  // Ensure the route exists for the test
  $this->secret = 'test_secret';
  Config::set('services.payment-point.secret', $this->secret);
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

  $_SERVER['HTTP_PAYMENTPOINT_SIGNATURE'] = $signature;
  withHeader('PAYMENTPOINT_SIGNATURE', $signature)
    ->post(route('payment-point.webhook'), $payload)
    ->assertOk();

  $user->refresh();
  expect($user->wallet)->toBe(floatval($settlementAmount));
});
