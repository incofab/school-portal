<?php

use App\Enums\InstitutionSettingType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Models\TermResult;
use App\Services\Messaging\Whatsapp\PhoneNumberNormalizer;
use App\Services\Messaging\Whatsapp\WhatsappConversationStateService;
use App\Services\Messaging\Whatsapp\WhatsappIntentResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
  config()->set('services.facebook.whatsapp-access-token', 'test-token');
  config()->set('services.facebook.whatsapp-phone-number-id', '123456');
  config()->set('services.facebook.whatsapp-webhook-verify-token', 'verify-me');
  config()->set('app.url', 'https://example.test');
  Cache::flush();
  Http::fake([
    'graph.facebook.com/*' => Http::response(
      ['messages' => [['id' => 'wamid.response']]],
      200
    )
  ]);
});

it('verifies meta webhook subscriptions', function () {
  $this->get(
    '/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=abc123'
  )
    ->assertOk()
    ->assertSee('abc123');

  $this->get(
    '/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=abc123'
  )->assertForbidden();
});

it('normalizes common phone number formats', function () {
  $normalizer = app(PhoneNumberNormalizer::class);

  expect($normalizer->normalize('+234 801-234-5678'))
    ->toBe('2348012345678')
    ->and($normalizer->normalize('08012345678'))
    ->toBe('2348012345678')
    ->and($normalizer->normalize('8012345678'))
    ->toBe('2348012345678')
    ->and($normalizer->lookupVariants('2348012345678'))
    ->toBe(['2348012345678', '08012345678', '8012345678']);
});

it('detects check result messages', function () {
  $resolver = app(WhatsappIntentResolver::class);

  expect($resolver->resolve('check my child result'))
    ->toBe(WhatsappIntentResolver::CHECK_RESULT)
    ->and($resolver->resolve('1'))
    ->toBe(WhatsappIntentResolver::CHECK_RESULT)
    ->and($resolver->resolve('hello'))
    ->toBe(WhatsappIntentResolver::UNKNOWN);
});

it('returns the menu for unknown messages', function () {
  postWhatsappText('2348012345678', 'hello');

  Http::assertSent(
    fn($request) => $request['to'] === '2348012345678' &&
      str_contains($request['text']['body'], '1. Check Result')
  );
});

it('sends a signed result link for one linked student', function () {
  [$student] = createStudentWithCurrentResult(
    phone: '08012345678',
    published: true,
    activated: true
  );

  postWhatsappText('2348012345678', 'check result');

  Http::assertSent(
    fn($request) => $request['to'] === '2348012345678' &&
      str_contains($request['text']['body'], $student->user->full_name) &&
      str_contains($request['text']['body'], 'signed-result-sheet')
  );
});

it(
  'creates selection state when a phone matches multiple students',
  function () {
    [$first] = createStudentWithCurrentResult(
      phone: '08012345678',
      published: true,
      activated: true
    );
    [$second] = createStudentWithCurrentResult(
      phone: '2348012345678',
      published: true,
      activated: true
    );

    postWhatsappText('2348012345678', 'check result');

    $state = app(WhatsappConversationStateService::class)->get('2348012345678');

    expect($state['student_ids'])->toBe([$first->id, $second->id]);
    Http::assertSent(
      fn($request) => str_contains(
        $request['text']['body'],
        'more than one student'
      ) &&
        str_contains($request['text']['body'], '1. ') &&
        str_contains($request['text']['body'], '2. ')
    );
  }
);

it('continues result checking after selecting a student', function () {
  createStudentWithCurrentResult(
    phone: '08012345678',
    published: true,
    activated: true
  );
  [$second] = createStudentWithCurrentResult(
    phone: '2348012345678',
    published: true,
    activated: true
  );

  postWhatsappText('2348012345678', 'check result', 'wamid.selection.start');
  postWhatsappText('2348012345678', '2', 'wamid.selection.pick');

  expect(
    app(WhatsappConversationStateService::class)->get('2348012345678')
  )->toBeNull();
  Http::assertSent(
    fn($request) => str_contains(
      $request['text']['body'],
      $second->user->full_name
    ) && str_contains($request['text']['body'], 'signed-result-sheet')
  );
});

it('asks again after an invalid student selection', function () {
  createStudentWithCurrentResult(
    phone: '08012345678',
    published: true,
    activated: true
  );
  createStudentWithCurrentResult(
    phone: '2348012345678',
    published: true,
    activated: true
  );

  postWhatsappText('2348012345678', 'check result', 'wamid.invalid.start');
  postWhatsappText('2348012345678', '9', 'wamid.invalid.pick');

  Http::assertSent(
    fn($request) => str_contains(
      $request['text']['body'],
      'valid student number'
    )
  );
});

it('reports unpublished results', function () {
  createStudentWithCurrentResult(
    phone: '08012345678',
    published: false,
    activated: true
  );

  postWhatsappText('2348012345678', 'check result');

  Http::assertSent(
    fn($request) => str_contains($request['text']['body'], 'not yet published')
  );
});

it('reports activation required results', function () {
  createStudentWithCurrentResult(
    phone: '08012345678',
    published: true,
    activated: false
  );

  postWhatsappText('2348012345678', 'check result');

  Http::assertSent(
    fn($request) => str_contains(
      $request['text']['body'],
      'needs to be activated'
    )
  );
});

function postWhatsappText(
  string $from,
  string $text,
  ?string $messageId = null
): void {
  $messageId ??=
    'wamid.' .
    fake()
      ->unique()
      ->uuid();

  \Pest\Laravel\postJson('/api/whatsapp/webhook', [
    'object' => 'whatsapp_business_account',
    'entry' => [
      [
        'changes' => [
          [
            'value' => [
              'messages' => [
                [
                  'from' => $from,
                  'id' => $messageId,
                  'type' => 'text',
                  'text' => ['body' => $text]
                ]
              ]
            ]
          ]
        ]
      ]
    ]
  ])->assertOk();
}

function createStudentWithCurrentResult(
  string $phone,
  bool $published,
  bool $activated
): array {
  $institution = Institution::factory()->create();
  $academicSession =
    AcademicSession::first() ??
    AcademicSession::factory()->create([
      'title' => '2025/2026'
    ]);
  InstitutionSetting::factory()->create([
    'institution_id' => $institution->id,
    'key' => InstitutionSettingType::CurrentAcademicSession->value,
    'value' => $academicSession->id
  ]);
  InstitutionSetting::factory()->create([
    'institution_id' => $institution->id,
    'key' => InstitutionSettingType::CurrentTerm->value,
    'value' => TermType::Second->value
  ]);

  $student = Student::factory()
    ->withInstitution($institution)
    ->create(['guardian_phone' => $phone]);

  $resultFactory = TermResult::factory()
    ->forStudent($student)
    ->state([
      'academic_session_id' => $academicSession->id,
      'term' => TermType::Second->value,
      'for_mid_term' => false,
      'is_activated' => $activated
    ]);

  $termResult = $published
    ? $resultFactory->published()->create()
    : $resultFactory->create();

  return [$student->fresh('user'), $termResult];
}
