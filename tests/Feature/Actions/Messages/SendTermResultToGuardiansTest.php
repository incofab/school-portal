<?php

use App\Actions\Messages\SendTermResultToGuardians;
use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Jobs\SendWhatsappTemplateMessage;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Message;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\Transaction;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplateResult;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
  Queue::fake();

  config()->set('services.facebook.whatsapp-access-token', 'test-token');
  config()->set(
    'services.facebook.whatsapp-phone-number-id',
    'test-phone-number-id'
  );

  $this->institution = Institution::factory()->create();
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->institutionGroup->fill(['credit_wallet' => 100])->save();
  $this->senderUser = $this->institution->createdBy;

  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
});

it(
  'queues whatsapp result templates and records message for multiple guardians',
  function () {
    config()->set('services.whatsapp-charge', 5);
    $academicSession = AcademicSession::factory()->create([
      'title' => '2025/2026'
    ]);

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
      ->create([
        'academic_session_id' => $academicSession->id,
        'for_mid_term' => true
      ]);
    $termResult2 = TermResult::factory()
      ->forStudent($student2)
      ->create([
        'academic_session_id' => $academicSession->id,
        'for_mid_term' => true
      ]);

    $action = new SendTermResultToGuardians(
      $this->institution,
      $this->senderUser
    );
    $response = $action->multiSend(collect([$termResult1, $termResult2]));

    expect($response->isSuccessful())->toBeTrue();
    expect($response->getMessage())->toBe('Results sent successfully');

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
      'recipient_contact' => '08011112222,08033334444'
    ]);

    Queue::assertPushed(SendWhatsappTemplateMessage::class, 2);
    Queue::assertPushed(SendWhatsappTemplateMessage::class, function ($job) {
      $ref = new ReflectionClass($job);
      $templateProperty = $ref->getProperty('whatsappTemplate');
      $templateProperty->setAccessible(true);
      $template = $templateProperty->getValue($job);

      if (!$template instanceof WhatsappTemplateResult) {
        return false;
      }

      $payload = $template->payload();
      $headerParameters = whatsappTemplateComponentParameters(
        $payload,
        'header'
      );
      $bodyParameters = whatsappTemplateComponentParameters($payload, 'body');

      return $payload['template']['name'] === 'student_result' &&
        $payload['template']['language']['code'] === 'en' &&
        in_array($payload['to'], ['2348011112222', '2348033334444'], true) &&
        $headerParameters[0]['text'] === $this->institution->name &&
        $headerParameters[0]['parameter_name'] === 'school_name' &&
        $bodyParameters[2]['text'] === 'First Mid-Term' &&
        $bodyParameters[2]['parameter_name'] === 'term' &&
        $bodyParameters[3]['text'] === '2025/2026 Session' &&
        $bodyParameters[3]['parameter_name'] === 'academic_session' &&
        str_contains($bodyParameters[4]['text'], 'signed-result-sheet') &&
        $bodyParameters[4]['parameter_name'] === 'result_link';
    });

    // Charges not applied at the moment
    // $transaction = Transaction::latest('id')->first();
    // expect($transaction)->not->toBeNull();
    // expect($transaction->type)->toBe(TransactionType::Debit);
    // expect($transaction->wallet)->toBe('credit');
    // expect((float) $transaction->amount)->toBe(10.0);
    // expect((float) $this->institutionGroup->fresh()->credit_wallet)->toBe(90.0);
  }
);

it(
  'does not record or queue when selected results have no whatsapp contact',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create(['guardian_phone' => null]);
    $student->user->forceFill(['phone' => null])->save();

    $termResult = TermResult::factory()
      ->forStudent($student)
      ->create(['for_mid_term' => true]);

    $action = new SendTermResultToGuardians(
      $this->institution,
      $this->senderUser
    );
    $response = $action->multiSend(collect([$termResult]));

    expect($response->isSuccessful())->toBeFalse();
    expect($response->getMessage())->toBe(
      'No guardian WhatsApp contact found for the selected results'
    );

    Queue::assertNothingPushed();
    expect(Message::count())->toBe(0);
  }
);

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
  expect((float) $this->institutionGroup->fresh()->credit_wallet)->toBe(100.0);
});

function whatsappTemplateComponentParameters(
  array $payload,
  string $componentType
): array {
  $component = collect($payload['template']['components'])->firstWhere(
    'type',
    $componentType
  );

  return $component['parameters'] ?? [];
}
