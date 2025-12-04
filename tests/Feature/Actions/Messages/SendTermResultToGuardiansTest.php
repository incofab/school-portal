<?php

use App\Actions\Messages\SendTermResultToGuardians;
use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Enums\TransactionType;
use App\Jobs\SendWhatsappMessage;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Message;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\Transaction;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;

beforeEach(function () {
  Queue::fake();

  $this->institution = Institution::factory()->create();
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->institutionGroup->fill(['credit_wallet' => 100])->save();
  $this->senderUser = $this->institution->createdBy;

  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('queues whatsapp templates and charges wallet for multiple guardians', function () {
  config()->set('services.whatsapp-charge', 5);

  $student1 = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->guardian($this->institution)
    ->create(['guardian_phone' => '08011112222']);
  $student2 = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->guardian($this->institution)
    ->create(['guardian_phone' => '08033334444']);

  $termResult1 = TermResult::factory()
    ->forStudent($student1)
    ->create(['for_mid_term' => true]);
  $termResult2 = TermResult::factory()
    ->forStudent($student2)
    ->create(['for_mid_term' => true]);

  $action = new SendTermResultToGuardians(
    $this->institution,
    $this->senderUser
  );
  $action->multiSend(collect([$termResult1, $termResult2]));

  $this->assertDatabaseHas('messages', [
    'institution_id' => $this->institution->id,
    'sender_user_id' => $this->senderUser->id,
    'subject' => 'Term Result',
    'channel' => NotificationChannelsType::Whatsapp->value,
    'recipient_category' => MessageRecipientCategory::Multiple->value,
    'status' => MessageStatus::Pending->value
  ]);

  $message = Message::first();

  $this->assertDatabaseHas('message_recipients', [
    'message_id' => $message->id,
    'institution_id' => $this->institution->id,
    'recipient_contact' => '2348011112222,2348033334444'
  ]);

  Queue::assertPushed(SendWhatsappMessage::class, function ($job) {
    $ref = new ReflectionClass($job);
    $payloadProperty = $ref->getProperty('multiplePayload');
    $payloadProperty->setAccessible(true);
    $payload = $payloadProperty->getValue($job);

    $recipients = collect($payload)->pluck('to')->all();

    return in_array('2348011112222', $recipients, true) &&
      in_array('2348033334444', $recipients, true);
  });

  $transaction = Transaction::latest('id')->first();
  expect($transaction)->not->toBeNull();
  expect($transaction->type)->toBe(TransactionType::Debit);
  expect($transaction->wallet)->toBe('credit');
  expect((float) $transaction->amount)->toBe(10.0);
  expect((float) $this->institutionGroup->fresh()->credit_wallet)->toBe(90.0);
});

it('returns a failure response when term result is not ready', function () {
  $termResult = TermResult::factory()
    ->forStudent(
      Student::factory()
        ->withInstitution($this->institution, $this->classification)
        ->guardian($this->institution)
        ->create(['guardian_phone' => '08055556666'])
    )
    ->create(['for_mid_term' => false, 'is_activated' => false]);

  $action = new SendTermResultToGuardians(
    $this->institution,
    $this->senderUser
  );
  $response = $action->multiSend(collect([$termResult]));

  expect($response->isSuccessful())->toBeFalse();
  expect($response->getMessage())->toBe('Result is not ready to be shared');

  Queue::assertNothingPushed();
  expect(Message::count())->toBe(0);
  expect(Transaction::count())->toBe(0);
  expect((float) $this->institutionGroup->fresh()->credit_wallet)->toBe(
    100.0
  );
});
