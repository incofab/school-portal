<?php

namespace Tests\Feature\Actions\Payments;

use App\Actions\Payments\RecordFeePaymentReminder;
use App\Enums\NotificationChannelsType;
use App\Enums\PriceLists\PriceType;
use App\Enums\SchoolNotificationPurpose;
use App\Jobs\SendBulksms;
use App\Mail\FeePaymentReminderMail;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use App\Models\Message;
use App\Models\PriceList;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * ./vendor/bin/pest --filter RecordFeePaymentReminderTest
 */

beforeEach(function () {
  Mail::fake();
  Queue::fake(); // Fake the queue to check for SendBulksms job

  $this->institution = Institution::factory()->create();
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->institutionGroup->fill(['credit_wallet' => 1000])->save(); // Set initial credit

  $this->adminUser = $this->institution->createdBy;

  // Setup students and guardians
  [$this->classification, $this->classification2] = Classification::factory(2)
    ->withInstitution($this->institution)
    ->create();

  $this->fee = Fee::factory()
    ->institution($this->institution)
    ->create(['amount' => 5000]);
  $this->feeCategory = FeeCategory::factory()
    ->fee($this->fee)
    ->feeable($this->classification)
    ->create();

  // Set up price lists
  $this->emailPrice = 10;
  $this->smsPrice = 15;
  PriceList::factory()
    ->for($this->institution->institutionGroup)
    ->type(PriceType::EmailSending)
    ->create(['amount' => $this->emailPrice]);
  PriceList::factory()
    ->for($this->institution->institutionGroup)
    ->type(PriceType::SmsSending)
    ->create(['amount' => $this->smsPrice]);

  // Student 1: Owes money, has guardian with email and phone
  [$this->student1, $this->student2] = Student::factory(2)
    ->withInstitution($this->institution, $this->classification)
    ->guardian($this->institution)
    ->create();
  $this->student3 = Student::factory()
    ->withInstitution($this->institution, $this->classification2)
    ->guardian($this->institution)
    ->create();
  $this->guardian1 = $this->student1->guardian;
  $this->guardian2 = $this->student2->guardian;
  $this->guardian3 = $this->student3->guardian;

  $this->reference = Str::orderedUuid()->toString();
});

it('sends fee payment reminders via email successfully', function () {
  $initialWallet = $this->institutionGroup->credit_wallet;
  $data = [
    'reference' => $this->reference,
    'channel' => NotificationChannelsType::Email->value
  ];

  $action = new RecordFeePaymentReminder(
    $this->adminUser,
    $data,
    $this->institution,
    $this->fee
  );
  $res = $action->run();

  expect($res->isSuccessful())->toBeTrue();

  // Assert SchoolNotification was created
  assertDatabaseHas('school_notifications', [
    'reference' => $this->reference,
    'sender_user_id' => $this->adminUser->id,
    'institution_id' => $this->institution->id
  ]);

  // Assert Message and MessageRecipient were created
  assertDatabaseHas('messages', [
    'institution_id' => $this->institution->id,
    'sender_user_id' => $this->adminUser->id,
    'channel' => NotificationChannelsType::Email->value
  ]);

  // Assert emails were queued for the correct guardians
  Mail::assertQueued(FeePaymentReminderMail::class, 2); // Guardians 1, 2, 5
  Mail::assertQueued(FeePaymentReminderMail::class, function ($mail) {
    return $mail->hasTo($this->guardian1->email);
  });
  Mail::assertQueued(FeePaymentReminderMail::class, function ($mail) {
    return $mail->hasTo($this->guardian2->email);
  });
  // Ensure email wasn't sent to guardian 3 (paid) or 4 (no email)
  Mail::assertNotQueued(FeePaymentReminderMail::class, function ($mail) {
    return $mail->hasTo($this->guardian3->email);
  });

  // Assert wallet deduction
  $expectedCost = 2 * $this->emailPrice;
  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(
    $initialWallet - $expectedCost
  );
});

it('sends fee payment reminders via sms successfully', function () {
  $initialWallet = $this->institutionGroup->credit_wallet;
  $data = [
    'reference' => $this->reference,
    'channel' => NotificationChannelsType::Sms->value
  ];

  $action = new RecordFeePaymentReminder(
    $this->adminUser,
    $data,
    $this->institution,
    $this->fee
  );
  $res = $action->run();

  expect($res->isSuccessful())->toBeTrue();

  // Assert SchoolNotification was created
  assertDatabaseHas('school_notifications', [
    'reference' => $this->reference,
    'sender_user_id' => $this->adminUser->id,
    'receiver_type' => MorphMap::key(User::class),
    'institution_id' => $this->institution->id,
    'purpose' => SchoolNotificationPurpose::Receipt->value
  ]);

  $notification = SchoolNotification::where(
    'reference',
    $this->reference
  )->first();
  // Guardians 1, 2, and 4 have phones and owe money
  expect($notification->receiver_ids->toArray())->toEqualCanonicalizing([
    $this->guardian1->id,
    $this->guardian2->id
  ]);

  // Assert Message and MessageRecipient were created
  assertDatabaseHas('messages', [
    'institution_id' => $this->institution->id,
    'sender_user_id' => $this->adminUser->id,
    'channel' => NotificationChannelsType::Sms->value,
    'messageable_type' => $notification->getMorphClass(),
    'messageable_id' => $notification->id
  ]);
  $message = Message::where('messageable_id', $notification->id)->first();
  assertDatabaseHas('message_recipients', [
    'message_id' => $message->id,
    'institution_id' => $this->institution->id,
    'recipient_contact' => implode(',', [
      $this->guardian1->phone,
      $this->guardian2->phone
    ])
  ]);

  // Assert SMS jobs were dispatched
  Queue::assertPushed(SendBulksms::class, 2); // Guardians 1, 2,
  Queue::assertPushed(SendBulksms::class, function ($job) {
    // dd(json_encode($job, JSON_PRETTY_PRINT));
    return $job->getTo() === $this->guardian1->phone;
  });
  Queue::assertPushed(SendBulksms::class, function ($job) {
    return $job->getTo() === $this->guardian2->phone;
  });
  // Ensure SMS wasn't sent to guardian 3 (paid) or 5 (no phone)
  Queue::assertNotPushed(SendBulksms::class, function ($job) {
    return $job->getTo() === $this->guardian3->phone;
  });

  // Assert wallet deduction
  $expectedCost = 2 * $this->smsPrice;
  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(
    $initialWallet - $expectedCost
  );
});

it('fails if no guardians with valid contact info are found', function () {
  $fee = Fee::factory()
    ->institution($this->institution)
    ->create(['amount' => 5000]);
  $feeCategory = FeeCategory::factory()
    ->fee($fee)
    ->feeable($this->classification2)
    ->create();

  // Remove contact info from guardians who owe money
  $this->guardian3->update(['email' => null, 'phone' => null]);

  $data = [
    'reference' => $this->reference,
    'channel' => NotificationChannelsType::Email->value // Try email first
  ];

  $action = new RecordFeePaymentReminder(
    $this->adminUser,
    $data,
    $this->institution,
    $fee
  );
  $res = $action->run();

  expect($res->isSuccessful())->toBeFalse();
  expect($res->getMessage())->toBe('No guardians found');

  assertDatabaseMissing('school_notifications', [
    'reference' => $this->reference
  ]);
  assertDatabaseMissing('messages', ['subject' => 'Fee Payment Reminder']);

  // Try SMS
  $data['channel'] = NotificationChannelsType::Sms->value;
  $action = new RecordFeePaymentReminder(
    $this->adminUser,
    $data,
    $this->institution,
    $fee
  );
  $res = $action->run();

  expect($res->isSuccessful())->toBeFalse();
  expect($res->getMessage())->toBe('No guardians found');
  assertDatabaseMissing('school_notifications', [
    'reference' => $this->reference
  ]);
  assertDatabaseMissing('messages', ['subject' => 'Fee Payment Reminder']);
});

it('fails if institution has insufficient wallet balance', function () {
  $this->institutionGroup->update(['credit_wallet' => 1]); // Set very low balance

  $data = [
    'reference' => $this->reference,
    'channel' => NotificationChannelsType::Email->value // Cost is 3 * 10 = 30
  ];

  $action = new RecordFeePaymentReminder(
    $this->adminUser,
    $data,
    $this->institution,
    $this->fee
  );
  $res = $action->run();

  expect($res->isSuccessful())->toBeFalse();
  expect($res->getMessage())->toBe('Insufficient wallet balance');

  // Assert SchoolNotification was created (charge happens after)
  assertDatabaseHas('school_notifications', ['reference' => $this->reference]);
  // Assert Message was created (charge happens after)
  assertDatabaseHas('messages', ['subject' => 'Fee Payment Reminder']);
  // Assert no emails were queued
  Mail::assertNothingQueued();
  // Assert wallet balance is unchanged
  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(floatval(1));
});

it('fails if price list is not set for the channel', function () {
  // Delete the email price list
  PriceList::where('institution_group_id', $this->institutionGroup->id)
    ->where('type', PriceType::EmailSending->value)
    ->delete();

  $data = [
    'reference' => $this->reference,
    'channel' => NotificationChannelsType::Email->value
  ];

  $action = new RecordFeePaymentReminder(
    $this->adminUser,
    $data,
    $this->institution,
    $this->fee
  );
  $res = $action->run();

  expect($res->isSuccessful())->toBeFalse();
  expect($res->getMessage())->toBe('Price List has not been set');

  // Assert SchoolNotification was created
  assertDatabaseHas('school_notifications', ['reference' => $this->reference]);
  // Assert Message was created
  assertDatabaseHas('messages', ['subject' => 'Fee Payment Reminder']);
  // Assert no emails were queued
  Mail::assertNothingQueued();
});
