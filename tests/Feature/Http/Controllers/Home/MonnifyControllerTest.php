<?php

use App\Enums\TransactionType;
use App\Models\ReservedAccount;
use App\Models\User;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
  Config::set('app.debug', false); // simulate production
  Config::set('services.monnify.secret', 'secret');
  Config::set('services.monnify.public', 'public');

  // Fake Monnify auth API call
  Http::fake([
    'https://api.monnify.com/api/v1/auth/login' => Http::response([
      'requestSuccessful' => true,
      'responseBody' => [
        'accessToken' => 'mock_token'
      ]
    ])
  ]);
});

it('processes Monnify webhook successfully', function () {
  $user = User::factory()->create(['wallet' => 0]);
  $paidOn = now()->toIso8601String();

  $reservedAccount = ReservedAccount::factory()->create([
    'reference' => $user->getReference(),
    'bank_code' => '999999',
    'account_number' => '1234567890',
    'reservable_type' => $user->getMorphClass(),
    'reservable_id' => $user->id
  ]);

  $webhookPayload = [
    'eventData' => [
      'paymentReference' => 'REF123456',
      'settlementAmount' => 2000,
      'amountPaid' => 2000,
      'totalPayable' => 2000,
      'paidOn' => $paidOn,
      'paymentStatus' => 'PAID',
      'transactionHash' => hash(
        'SHA512',
        'secret|REF123456|2000|' . $paidOn . '|REF123456'
      ),
      'product' => [
        'reference' => $reservedAccount->reference,
        'type' => 'RESERVED_ACCOUNT'
      ],
      'destinationAccountInformation' => [
        'bankCode' => $reservedAccount->bank_code,
        'accountNumber' => $reservedAccount->account_number
      ]
    ]
  ];

  Http::fake([
    'https://api.monnify.com/api/v2/merchant/transactions/query*' => Http::response(
      [
        'requestSuccessful' => true,
        'responseBody' => [
          'paymentStatus' => 'PAID',
          'settlementAmount' => 2000
        ]
      ],
      200
    )
  ]);

  // Simulate request from Monnify IP
  $this->postJson(route('monnify.webhook'), $webhookPayload, [
    'REMOTE_ADDR' => '35.242.133.146',
    'X-Forwarded-For' => '35.242.133.146'
  ])->assertOk();

  $user->refresh();

  expect($user->wallet)->toBe(floatval(2000));

  $this->assertDatabaseHas('user_transactions', [
    'reference' => 'REF123456',
    'amount' => 2000,
    'entity_id' => $user->id,
    'type' => TransactionType::Credit->value
  ]);
});

it(
  'does not duplicate reserved-account credits for duplicate Monnify webhooks',
  function () {
    $user = User::factory()->create(['wallet' => 0]);
    $paidOn = now()->toIso8601String();

    $reservedAccount = ReservedAccount::factory()->create([
      'reference' => $user->getReference(),
      'bank_code' => '999999',
      'account_number' => '1234567890',
      'reservable_type' => $user->getMorphClass(),
      'reservable_id' => $user->id
    ]);

    $webhookPayload = [
      'eventData' => [
        'paymentReference' => 'REF-DUP-MONNIFY',
        'settlementAmount' => 2000,
        'amountPaid' => 2000,
        'totalPayable' => 2000,
        'paidOn' => $paidOn,
        'paymentStatus' => 'PAID',
        'transactionHash' => hash(
          'SHA512',
          'secret|REF-DUP-MONNIFY|2000|' . $paidOn . '|REF-DUP-MONNIFY'
        ),
        'product' => [
          'reference' => $reservedAccount->reference,
          'type' => 'RESERVED_ACCOUNT'
        ],
        'destinationAccountInformation' => [
          'bankCode' => $reservedAccount->bank_code,
          'accountNumber' => $reservedAccount->account_number
        ]
      ]
    ];

    Http::fake([
      'https://api.monnify.com/api/v2/merchant/transactions/query*' => Http::response(
        [
          'requestSuccessful' => true,
          'responseBody' => [
            'paymentStatus' => 'PAID',
            'settlementAmount' => 2000
          ]
        ],
        200
      )
    ]);

    $this->postJson(route('monnify.webhook'), $webhookPayload, [
      'REMOTE_ADDR' => '35.242.133.146',
      'X-Forwarded-For' => '35.242.133.146'
    ])->assertOk();
    $this->postJson(route('monnify.webhook'), $webhookPayload, [
      'REMOTE_ADDR' => '35.242.133.146',
      'X-Forwarded-For' => '35.242.133.146'
    ])->assertOk();

    expect($user->fresh()->wallet)->toBe(2000.0);
    expect(
      UserTransaction::query()
        ->where('reference', 'REF-DUP-MONNIFY')
        ->count()
    )->toBe(1);
  }
);

it('rejects Monnify webhooks with an invalid transaction hash', function () {
  $this->postJson(
    route('monnify.webhook'),
    [
      'eventData' => [
        'paymentReference' => 'REF-BAD-HASH',
        'settlementAmount' => 2000,
        'amountPaid' => 2000,
        'paidOn' => now()->toIso8601String(),
        'transactionHash' => 'invalid',
        'product' => [
          'reference' => 'reserved-reference',
          'type' => 'RESERVED_ACCOUNT'
        ]
      ]
    ],
    [
      'REMOTE_ADDR' => '35.242.133.146',
      'X-Forwarded-For' => '35.242.133.146'
    ]
  )->assertStatus(400);
});

it('rejects webhook from unauthorized IP', function () {
  $this->postJson(
    route('monnify.webhook'),
    [],
    [
      'REMOTE_ADDR' => '192.168.1.1',
      'X-Forwarded-For' => '192.168.1.1'
    ]
  )->assertForbidden();
});
