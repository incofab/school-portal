<?php

use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{actingAs, assertDatabaseHas, assertDatabaseMissing};

uses(RefreshDatabase::class);

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->user = User::factory()->admin($this->institution);
  $this->receiptType = ReceiptType::factory()->institution($this->institution);
});

test('index method returns receipt types', function () {
  actingAs($this->user)
    ->get(
      route('institutions.receipt-types.index', [
        'institution' => $this->institution->uuid
      ])
    )
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/payments/list-receipt-types')
        ->has('receiptTypes.data')
    );
});

test('search method returns receipt types based on search query', function () {
  actingAs($this->user)
    ->get(
      route('institutions.receipt-types.search', [
        'institution' => $this->institution->uuid,
        'search' => $this->receiptType->title
      ])
    )
    ->assertOk()
    ->assertJsonFragment(['title' => $this->receiptType->title]);
});

test('store method creates a new receipt type', function () {
  $data = [
    'title' => 'New Receipt Type',
    'descriptions' => 'Description for the new receipt type'
  ];

  actingAs($this->user)
    ->post(
      route('institutions.receipt-types.store', [
        'institution' => $this->institution->uuid
      ]),
      $data
    )
    ->assertStatus(200);

  assertDatabaseHas('receipt_types', ['title' => 'New Receipt Type']);
});

test('update method updates an existing receipt type', function () {
  $data = [
    'title' => 'Updated Receipt Type',
    'descriptions' => 'Updated description'
  ];

  actingAs($this->user)
    ->put(
      route('institutions.receipt-types.update', [
        'institution' => $this->institution->uuid,
        'receiptType' => $this->receiptType->id
      ]),
      $data
    )
    ->assertOk();

  assertDatabaseHas('receipt_types', ['title' => 'Updated Receipt Type']);
});

test('destroy method deletes a receipt type', function () {
  actingAs($this->user)
    ->delete(
      route('institutions.receipt-types.destroy', [
        'institution' => $this->institution->uuid,
        'receiptType' => $this->receiptType->id
      ])
    )
    ->assertStatus(200);

  assertDatabaseMissing('receipt_types', ['id' => $this->receiptType->id]);
});
