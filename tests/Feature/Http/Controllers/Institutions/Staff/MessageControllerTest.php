<?php

use App\Enums\NotificationChannelsType;
use App\Enums\PriceLists\PriceType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Message;
use App\Models\PriceList;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->institutionGroup->fill(['credit_wallet' => 0])->save();
  $this->adminUser = $this->institution->createdBy;
  $this->nonAdminUser = User::factory()
    ->teacher($this->institution)
    ->create();

  Mail::fake();
  Queue::fake();
});

test('message index shows messages for the institution', function () {
  // Create messages specific to this institution
  Message::factory()
    ->count(2)
    ->institution($this->institution)
    ->create();

  actingAs($this->adminUser)
    ->get(route('institutions.messages.index', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/messages/list-messages')
        ->has('messages')
        ->has('messages.data', 2) // Check pagination data count
        ->where('messages.data.0.institution_id', $this->institution->id)
    );
});

test('can store message to a specific model (classification)', function () {
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  [$student1, $student2] = Student::factory(2)
    ->withInstitution($this->institution, $classification)
    ->create();
  $messageText = 'This is a test message for the classification';
  $channel = NotificationChannelsType::Sms->value;

  $data = [
    'message' => $messageText,
    'channel' => $channel,
    'messageable_type' => 'classification', // Morph alias or full class name
    'messageable_id' => $classification->id,
    'to_guardians' => false,
    'reference' => Str::uuid()->toString()
  ];

  // Not pricelist set, should fail
  actingAs($this->adminUser)
    ->post(route('institutions.messages.store', $this->institution), $data)
    ->assertForbidden()
    ->assertJson(['message' => 'Price List has not been set']);

  $priceList1 = PriceList::factory()
    ->for($this->institution->institutionGroup)
    ->type(PriceType::EmailSending)
    ->create(['amount' => 10]);
  $priceList2 = PriceList::factory()
    ->for($this->institution->institutionGroup)
    ->type(PriceType::SmsSending)
    ->create(['amount' => 10]);

  actingAs($this->adminUser)
    ->post(route('institutions.messages.store', $this->institution), $data)
    ->assertForbidden()
    ->assertJson(['message' => 'Insufficient wallet balance']);

  $this->institutionGroup->fill(['credit_wallet' => 100])->save();

  actingAs($this->adminUser)
    ->post(route('institutions.messages.store', $this->institution), $data)
    ->assertOk();

  // Assert message was recorded in DB (optional, handler mock covers intent)
  assertDatabaseHas('messages', [
    'institution_id' => $this->institution->id,
    'sender_user_id' => $this->adminUser->id,
    'body' => $messageText,
    'channel' => $channel
  ]);
  assertDatabaseHas('message_recipients', [
    'institution_id' => $this->institution->id,
    'recipient_type' => $classification->getMorphClass(),
    'recipient_id' => $classification->id
  ]);
  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(
    $this->institutionGroup->credit_wallet - $priceList1->amount * 2
  );
});

test('can store message to a list of receivers (email)', function () {
  PriceList::factory()
    ->for($this->institution->institutionGroup)
    ->type(PriceType::EmailSending)
    ->create(['amount' => 10]);
  $this->institutionGroup->fill(['credit_wallet' => 100])->save();

  $receivers = ['test1@example.com', 'test2@example.com'];
  $messageText = 'This is a test email';
  $subject = 'Test Subject';
  $channel = NotificationChannelsType::Email->value;

  $data = [
    'message' => $messageText,
    'subject' => $subject,
    'channel' => $channel,
    'receivers' => implode(',', $receivers),
    'to_guardians' => false, // Should be ignored when receivers are present
    'reference' => Str::uuid()->toString()
  ];

  actingAs($this->adminUser)
    ->post(route('institutions.messages.store', $this->institution), $data)
    ->assertOk()
    ->assertJson(['ok' => true]);

  // Assert message was recorded in DB
  $this->assertDatabaseHas('messages', [
    'institution_id' => $this->institution->id,
    'sender_user_id' => $this->adminUser->id,
    'body' => $messageText,
    'subject' => $subject,
    'channel' => $channel
  ]);
  $this->assertDatabaseHas('message_recipients', [
    'institution_id' => $this->institution->id,
    'recipient_contact' => implode(',', $receivers)
  ]);
});

// Validation tests

test(
  'store validation fails for missing subject when channel is email',
  function () {
    $data = [
      'message' => 'Test',
      'channel' => NotificationChannelsType::Email->value,
      'receivers' => 'test@example.com',
      'to_guardians' => false,
      'reference' => Str::uuid()->toString()
    ];
    actingAs($this->adminUser)
      ->post(route('institutions.messages.store', $this->institution), $data)
      ->assertSessionHasErrors(['subject']);
  }
);

test('store validation fails for invalid messageable type', function () {
  $data = [
    'message' => 'Test',
    'channel' => NotificationChannelsType::Sms->value,
    'messageable_type' => 'invalid-morph-type',
    'messageable_id' => 1,
    'to_guardians' => false,
    'reference' => Str::uuid()->toString()
  ];
  actingAs($this->adminUser)
    ->post(route('institutions.messages.store', $this->institution), $data)
    ->assertSessionHasErrors(['messageable_type']);
});

test('store validation fails for non-existent messageable id', function () {
  $data = [
    'message' => 'Test',
    'channel' => NotificationChannelsType::Sms->value,
    'messageable_type' => 'classification',
    'messageable_id' => 9999, // Non-existent ID
    'to_guardians' => false,
    'reference' => Str::uuid()->toString()
  ];
  actingAs($this->adminUser)
    ->post(route('institutions.messages.store', $this->institution), $data)
    ->assertSessionHasErrors(['messageable_type']); // Assumes ValidateMorphRule handles this
});

test(
  'store validation fails if neither receivers nor messageable is provided',
  function () {
    $data = [
      'message' => 'Test',
      'channel' => NotificationChannelsType::Sms->value,
      'to_guardians' => false,
      'reference' => Str::uuid()->toString()
    ];
    actingAs($this->adminUser)
      ->post(route('institutions.messages.store', $this->institution), $data)
      ->assertSessionHasErrors(['messageable_type', 'messageable_id']); // Expects these to be required if receivers is empty
  }
);
