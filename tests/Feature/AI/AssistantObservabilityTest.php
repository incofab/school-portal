<?php

use App\Contracts\AI\AssistantTextGenerator;
use App\DTO\AI\AssistantGenerationResult;
use App\Enums\ManagerRole;
use App\Models\AssistantRunMetric;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\User;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantConversationService;
use App\Services\AI\AssistantTelemetry;
use App\Support\AI\AssistantDiagnostics;
use App\Services\AI\LaravelAiTextAgent;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  actingAs($this->admin);
});

function observedConversation($institution)
{
  return app(AssistantConversationService::class)->create(
    app(AssistantContextResolver::class)->resolve($institution),
    null
  );
}

function sendObserved($test, $institution, $conversation, string $message)
{
  return $test->postJson(
    route('institutions.assistant.messages.store', [
      $institution,
      $conversation
    ]),
    ['message' => $message]
  );
}

it(
  'records one metric per answered turn without storing the message',
  function () {
    LaravelAiTextAgent::fake(['A helpful answer.']);
    $conversation = observedConversation($this->institution);

    sendObserved(
      $this,
      $this->institution,
      $conversation,
      'Tell me something about EduManager'
    )->assertOk();

    $metric = AssistantRunMetric::query()
      ->latest('id')
      ->first();

    expect($metric)
      ->not->toBeNull()
      ->and($metric->outcome)
      ->toBe(AssistantTelemetry::OUTCOME_ANSWERED)
      ->and($metric->institution_id)
      ->toBe($this->institution->id)
      ->and($metric->user_id)
      ->toBe($this->admin->id)
      ->and($metric->is_guest)
      ->toBeFalse()
      ->and($metric->model_calls)
      ->toBe(1)
      ->and($metric->message_id)
      ->not->toBeNull();

    // Telemetry must never become a second copy of the conversation.
    expect(json_encode($metric->toArray()))
      ->not->toContain('Tell me something about EduManager')
      ->not->toContain('A helpful answer.');
  }
);

it('labels clarification, action, and failure turns distinctly', function () {
  LaravelAiTextAgent::fake(['An answer.']);
  $group = ClassificationGroup::factory()
    ->withInstitution($this->institution)
    ->create();
  $conversation = observedConversation($this->institution);

  sendObserved(
    $this,
    $this->institution,
    $conversation,
    'Show me the result'
  )->assertOk();
  sendObserved(
    $this,
    $this->institution,
    $conversation,
    "Create class JSS 6 Gold in group {$group->id}"
  )->assertOk();

  $outcomes = AssistantRunMetric::query()
    ->pluck('outcome')
    ->all();

  expect($outcomes)->toContain(
    AssistantTelemetry::OUTCOME_CLARIFIED,
    AssistantTelemetry::OUTCOME_ACTION_PREPARED
  );
});

it('records a categorised failure when the provider errors', function () {
  app()->instance(
    AssistantTextGenerator::class,
    new class implements AssistantTextGenerator {
      public function generate(
        string $systemPrompt,
        string $prompt,
        array $options = []
      ): AssistantGenerationResult {
        throw new RuntimeException('Provider credentials must not leak.');
      }
    }
  );
  $conversation = observedConversation($this->institution);

  sendObserved(
    $this,
    $this->institution,
    $conversation,
    'Explain result publishing to me'
  )->assertStatus(503);

  $metric = AssistantRunMetric::query()
    ->latest('id')
    ->first();

  expect($metric->outcome)
    ->toBe(AssistantTelemetry::OUTCOME_FAILED)
    ->and($metric->failure_reason)
    ->toBe('provider_error');
});

it('records an empty provider reply as its own failure category', function () {
  LaravelAiTextAgent::fake(['']);
  $conversation = observedConversation($this->institution);

  sendObserved(
    $this,
    $this->institution,
    $conversation,
    'Explain result publishing to me'
  )->assertStatus(503);

  expect(
    AssistantRunMetric::query()
      ->latest('id')
      ->value('failure_reason')
  )->toBe('empty_response');
});

it('attaches a viewer rating to the run that produced the answer', function () {
  LaravelAiTextAgent::fake(['An answer.']);
  $conversation = observedConversation($this->institution);

  $sent = sendObserved(
    $this,
    $this->institution,
    $conversation,
    'What is EduManager?'
  )->assertOk();

  $this->postJson($sent->json('conversation.messages.1.links.feedback'), [
    'rating' => 'helpful'
  ])->assertOk();

  expect(
    AssistantRunMetric::query()
      ->where('message_id', $sent->json('conversation.messages.1.id'))
      ->value('feedback_rating')
  )->toBe('helpful');
});

it('stops recording telemetry when it is switched off', function () {
  config(['ai.telemetry.enabled' => false]);
  LaravelAiTextAgent::fake(['An answer.']);
  $conversation = observedConversation($this->institution);

  sendObserved(
    $this,
    $this->institution,
    $conversation,
    'What is EduManager?'
  )->assertOk();

  expect(AssistantRunMetric::query()->count())->toBe(0);
});

it('aggregates diagnostics from recorded runs only', function () {
  AssistantRunMetric::query()->create([
    'conversation_id' => 'c1',
    'institution_id' => $this->institution->id,
    'actor_role' => 'admin',
    'outcome' => 'answered',
    'duration_ms' => 1000,
    'model_calls' => 1,
    'grounded' => true,
    'source_count' => 2,
    'input_tokens' => 100,
    'output_tokens' => 50,
    'feedback_rating' => 'helpful'
  ]);
  AssistantRunMetric::query()->create([
    'conversation_id' => 'c2',
    'institution_id' => $this->institution->id,
    'actor_role' => 'teacher',
    'outcome' => 'failed',
    'failure_reason' => 'provider_error',
    'duration_ms' => 3000,
    'model_calls' => 1
  ]);

  $diagnostics = AssistantDiagnostics::forDays(30);
  $summary = $diagnostics->summary();

  expect($summary['runs'])
    ->toBe(2)
    ->and($summary['failures'])
    ->toBe(1)
    ->and($summary['failure_rate'])
    ->toBe(50.0)
    ->and($summary['grounded_rate'])
    ->toBe(50.0)
    ->and($summary['input_tokens'])
    ->toBe(100)
    ->and($diagnostics->failuresByReason())
    ->toBe(['provider_error' => 1])
    ->and($diagnostics->feedback())
    ->toBe(['helpful' => 1, 'not_helpful' => 0, 'rated' => 1])
    ->and(
      collect($diagnostics->byRole())
        ->pluck('role')
        ->all()
    )
    ->toContain('admin', 'teacher')
    ->and($diagnostics->busiestInstitutions()[0]['runs'])
    ->toBe(2);
});

it(
  'reports the operational controls an owner needs to throttle or disable it',
  function () {
    config([
      'ai.assistant.enabled' => false,
      'ai.assistant.rate_limit' => 7
    ]);

    $controls = AssistantDiagnostics::operationalControls();

    expect($controls['assistant_enabled'])
      ->toBeFalse()
      ->and($controls['rate_limit'])
      ->toBe(7)
      ->and($controls)
      ->toHaveKeys([
        'guest_access',
        'max_model_calls',
        'max_tool_calls',
        'max_retrieval_calls',
        'timeout_seconds'
      ]);
  }
);

it('shows assistant diagnostics only to a manager admin', function () {
  $managerAdmin = User::factory()->create();
  $managerAdmin->assignRole(ManagerRole::ManagerAdmin->value);

  actingAs($managerAdmin)
    ->get(route('managers.ai-assistant.diagnostics'))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('managers/ai-assistant/diagnostics')
        ->has('summary')
        ->has('controls')
    );

  $partner = User::factory()->create();
  $partner->assignRole(ManagerRole::Partner->value);
  actingAs($partner)
    ->get(route('managers.ai-assistant.diagnostics'))
    ->assertForbidden();

  actingAs($this->admin)
    ->get(route('managers.ai-assistant.diagnostics'))
    ->assertRedirect();
});
