<?php

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Enums\TermType;
use App\Jobs\SendWhatsappTemplateMessage;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Message;
use App\Models\Student;
use App\Models\TermResult;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplateResult;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplateUtility;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
  config()->set('app.url', 'https://example.test');
  config()->set('services.facebook.whatsapp-access-token', 'test-token');
  config()->set(
    'services.facebook.whatsapp-phone-number-id',
    'test-phone-number-id'
  );
  config()->set('services.facebook.whatsapp-api-version', 'v25.0');
});

it(
  'builds utility template payloads with normalized recipient numbers',
  function () {
    $template = new WhatsappTemplateUtility(
      receiverPhoneNumber: '08012345678',
      schoolName: 'Success Academy',
      receiverName: 'Parent',
      message: 'Your child was absent today.'
    );

    expect($template->getTemplateName())->toBe('utility_message');
    expect($template->getReceiverPhoneNumber())->toBe('08012345678');

    $payload = $template->payload();

    expect($payload['messaging_product'])->toBe('whatsapp');
    expect($payload['to'])->toBe('2348012345678');
    expect($payload['type'])->toBe('template');
    expect($payload['template']['name'])->toBe('utility_message');
    expect($payload['template']['language']['code'])->toBe('en');
    expect(whatsappTemplateTestComponentParameters($payload, 'header'))->toBe([
      [
        'type' => 'text',
        'text' => 'Success Academy',
        'parameter_name' => 'school_name'
      ]
    ]);
    expect(whatsappTemplateTestComponentParameters($payload, 'body'))->toBe([
      ['type' => 'text', 'text' => 'Parent', 'parameter_name' => 'name'],
      [
        'type' => 'text',
        'text' => 'Your child was absent today.',
        'parameter_name' => 'message'
      ]
    ]);
  }
);

it(
  'builds result templates from term results using guardian details first',
  function () {
    $institution = Institution::factory()->create();
    $classification = Classification::factory()
      ->withInstitution($institution)
      ->create();
    $academicSession = AcademicSession::factory()->create([
      'title' => '2025/2026'
    ]);
    $student = Student::factory()
      ->withInstitution($institution, $classification)
      ->guardian($institution)
      ->create(['guardian_phone' => '08022223333']);
    $guardian = $student->fresh('guardian')->guardian;
    $guardian
      ->forceFill([
        'first_name' => 'Grace',
        'other_names' => null,
        'last_name' => 'Parent'
      ])
      ->save();

    $termResult = TermResult::factory()
      ->forStudent($student)
      ->published()
      ->create([
        'academic_session_id' => $academicSession->id,
        'term' => TermType::Second,
        'for_mid_term' => false,
        'is_activated' => true
      ]);

    $template = WhatsappTemplateResult::fromTermResult(
      $termResult->fresh('student.user', 'student.guardian', 'academicSession')
    );

    expect($template)->toBeInstanceOf(WhatsappTemplateResult::class);
    expect($template->getReceiverPhoneNumber())->toBe('08022223333');

    $payload = $template->payload();
    $headerParameters = whatsappTemplateTestComponentParameters(
      $payload,
      'header'
    );
    $bodyParameters = whatsappTemplateTestComponentParameters($payload, 'body');

    expect($payload['to'])->toBe('2348022223333');
    expect($payload['template']['name'])->toBe('student_result');
    expect($payload['template']['language']['code'])->toBe('en');
    expect($headerParameters[0]['text'])->toBe($institution->name);
    expect($headerParameters[0]['parameter_name'])->toBe('school_name');
    expect($bodyParameters[0]['text'])->toBe('Grace Parent');
    expect($bodyParameters[0]['parameter_name'])->toBe('receiver_name');
    expect($bodyParameters[1]['text'])->toBe($student->user->full_name);
    expect($bodyParameters[1]['parameter_name'])->toBe('student_name');
    expect($bodyParameters[2]['text'])->toBe('Second Term');
    expect($bodyParameters[2]['parameter_name'])->toBe('term');
    expect($bodyParameters[3]['text'])->toBe('2025/2026 Session');
    expect($bodyParameters[3]['parameter_name'])->toBe('academic_session');
    expect($bodyParameters[4]['text'])->toContain('signed-result-sheet');
    expect($bodyParameters[4]['parameter_name'])->toBe('result_link');
  }
);

it(
  'falls back to student phone when guardian contact is unavailable',
  function () {
    $institution = Institution::factory()->create();
    $classification = Classification::factory()
      ->withInstitution($institution)
      ->create();
    $student = Student::factory()
      ->withInstitution($institution, $classification)
      ->create(['guardian_phone' => null]);
    $student->user->forceFill(['phone' => '08099990000'])->save();

    $termResult = TermResult::factory()
      ->forStudent($student)
      ->create(['for_mid_term' => true]);

    $template = WhatsappTemplateResult::fromTermResult(
      $termResult->fresh('student.user', 'student.guardian', 'academicSession')
    );

    expect($template)->toBeInstanceOf(WhatsappTemplateResult::class);
    expect($template->getReceiverPhoneNumber())->toBe('08099990000');
    expect($template->payload()['to'])->toBe('2348099990000');
  }
);

it('returns null for result templates without any receiver phone', function () {
  $institution = Institution::factory()->create();
  $classification = Classification::factory()
    ->withInstitution($institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($institution, $classification)
    ->create(['guardian_phone' => null]);
  $student->user->forceFill(['phone' => null])->save();

  $termResult = TermResult::factory()
    ->forStudent($student)
    ->create(['for_mid_term' => true]);

  expect(
    WhatsappTemplateResult::fromTermResult(
      $termResult->fresh('student.user', 'student.guardian', 'academicSession')
    )
  )->toBeNull();
});

it(
  'sends template payloads to the configured whatsapp cloud endpoint',
  function () {
    Http::fake([
      'graph.facebook.com/*' => Http::response(
        ['messages' => [['id' => 'wamid.template']]],
        200
      )
    ]);

    $template = new WhatsappTemplateUtility(
      receiverPhoneNumber: '08012345678',
      schoolName: 'Success Academy',
      receiverName: 'Parent',
      message: 'Your child was absent today.'
    );

    $response = $template->send();

    expect($response->isSuccessful())->toBeTrue();
    expect($response->getMessage())->toBe('Message sent successfully');

    Http::assertSent(
      fn($request) => $request->url() ===
        'https://graph.facebook.com/v25.0/test-phone-number-id/messages' &&
        $request->hasHeader('Authorization', 'Bearer test-token') &&
        $request['template']['name'] === 'utility_message' &&
        $request['to'] === '2348012345678'
    );
  }
);

it(
  'returns a failure response when whatsapp cloud rejects a template',
  function () {
    Http::fake([
      'graph.facebook.com/*' => Http::response(['error' => 'bad request'], 400)
    ]);

    $response = (new WhatsappTemplateUtility(
      receiverPhoneNumber: '08012345678',
      schoolName: 'Success Academy',
      receiverName: 'Parent',
      message: 'Your child was absent today.'
    ))->send();

    expect($response->isSuccessful())->toBeFalse();
    expect($response->getMessage())->toBe('Failed to send message');
    expect($response->error)->toBe('bad request');
  }
);

it(
  'marks related messages as sent only when the template send succeeds',
  function () {
    Http::fake([
      'graph.facebook.com/*' => Http::response(
        ['messages' => [['id' => 'wamid.template']]],
        200
      )
    ]);
    $message = Message::factory()->create([
      'status' => MessageStatus::Pending->value,
      'sent_at' => null
    ]);

    (new SendWhatsappTemplateMessage(
      new WhatsappTemplateUtility(
        receiverPhoneNumber: '08012345678',
        schoolName: 'Success Academy',
        receiverName: 'Parent',
        message: 'Your child was absent today.'
      ),
      $message
    ))->handle();

    expect($message->fresh()->status)->toBe(MessageStatus::Sent);
    expect($message->fresh()->sent_at)->not->toBeNull();
  }
);

it('keeps related messages pending when the template send fails', function () {
  Http::fake([
    'graph.facebook.com/*' => Http::response(['error' => 'bad request'], 400)
  ]);
  $message = Message::factory()->create([
    'status' => MessageStatus::Pending->value,
    'sent_at' => null
  ]);

  (new SendWhatsappTemplateMessage(
    new WhatsappTemplateUtility(
      receiverPhoneNumber: '08012345678',
      schoolName: 'Success Academy',
      receiverName: 'Parent',
      message: 'Your child was absent today.'
    ),
    $message
  ))->handle();

  expect($message->fresh()->status)->toBe(MessageStatus::Pending);
  expect($message->fresh()->sent_at)->toBeNull();
});

it(
  'dispatches ordinary whatsapp messages with the utility template',
  function () {
    Queue::fake();
    $institution = Institution::factory()->create([
      'name' => 'Success Academy'
    ]);
    $message = Message::factory()
      ->institution($institution)
      ->create(['status' => MessageStatus::Pending->value]);

    (new MessageDispatcher($institution))->dispatch(
      receivers: collect(['08012345678']),
      channel: NotificationChannelsType::Whatsapp,
      message: 'Your child was absent today.',
      subject: 'Attendance Notice',
      messageModel: $message
    );

    Queue::assertPushed(SendWhatsappTemplateMessage::class, function ($job) {
      $ref = new ReflectionClass($job);
      $templateProperty = $ref->getProperty('whatsappTemplate');
      $templateProperty->setAccessible(true);
      $template = $templateProperty->getValue($job);

      if (!$template instanceof WhatsappTemplateUtility) {
        return false;
      }

      $payload = $template->payload();
      $headerParameters = whatsappTemplateTestComponentParameters(
        $payload,
        'header'
      );
      $bodyParameters = whatsappTemplateTestComponentParameters(
        $payload,
        'body'
      );

      return $payload['to'] === '2348012345678' &&
        $headerParameters[0]['text'] === 'Success Academy' &&
        $bodyParameters[0]['text'] === '|Attendance Notice|' &&
        $bodyParameters[0]['parameter_name'] === 'name' &&
        $bodyParameters[1]['text'] === 'Your child was absent today.' &&
        $bodyParameters[1]['parameter_name'] === 'message';
    });
  }
);

function whatsappTemplateTestComponentParameters(
  array $payload,
  string $componentType
): array {
  $component = collect($payload['template']['components'])->firstWhere(
    'type',
    $componentType
  );

  return $component['parameters'] ?? [];
}
