<?php

use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\Institution;
use App\Models\User;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantConversationService;
use App\Services\AI\LaravelAiTextAgent;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

function startConversation($institution): AiConversation
{
  return app(AssistantConversationService::class)->create(
    app(AssistantContextResolver::class)->resolve($institution),
    null
  );
}

it(
  'offers optional role-aware suggestions on the assistant surfaces',
  function () {
    $this->get(route('assistant.index'))
      ->assertOk()
      ->assertInertia(
        fn($page) => $page
          ->has('suggestions', 4)
          ->where('actor.is_guest', true)
          ->where('actor.can_run_actions', false)
      );

    actingAs($this->admin)
      ->get(route('institutions.assistant.index', $this->institution))
      ->assertOk()
      ->assertInertia(
        fn($page) => $page
          ->has('suggestions')
          ->where('actor.can_run_actions', true)
      );

    $teacher = User::factory()
      ->teacher($this->institution)
      ->create();
    $teacherSuggestions = actingAs($teacher)
      ->get(route('institutions.assistant.index', $this->institution))
      ->assertOk()
      ->viewData('page')['props']['suggestions'];
    $adminSuggestions = actingAs($this->admin)
      ->get(route('institutions.assistant.index', $this->institution))
      ->assertOk()
      ->viewData('page')['props']['suggestions'];

    expect($teacherSuggestions)->not->toBe($adminSuggestions);
  }
);

it('still answers a message that matches no suggestion', function () {
  LaravelAiTextAgent::fake([
    'Here is what I can tell you.'
  ]);
  actingAs($this->admin);
  $conversation = startConversation($this->institution);

  $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    [
      'message' =>
        'this is nothing like any suggested prompt, just rambling about whatever'
    ]
  )
    ->assertOk()
    ->assertJsonPath('conversation.messages.1.role', 'assistant');
});

it('exposes a feedback link on assistant messages only', function () {
  LaravelAiTextAgent::fake(['An answer.']);
  actingAs($this->admin);
  $conversation = startConversation($this->institution);

  $response = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    ['message' => 'What is EduManager?']
  )->assertOk();

  expect($response->json('conversation.messages.0.links'))
    ->toBeNull()
    ->and($response->json('conversation.messages.1.links.feedback'))
    ->toBeString();
});

it('records helpful and not helpful feedback on an owned answer', function () {
  LaravelAiTextAgent::fake(['An answer.']);
  actingAs($this->admin);
  $conversation = startConversation($this->institution);

  $sent = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    ['message' => 'What is EduManager?']
  )->assertOk();
  $feedbackUrl = $sent->json('conversation.messages.1.links.feedback');

  $this->postJson($feedbackUrl, ['rating' => 'helpful'])
    ->assertOk()
    ->assertJsonPath('conversation.messages.1.feedback.rating', 'helpful');

  $this->postJson($feedbackUrl, [
    'rating' => 'not_helpful',
    'note' => 'It missed the fee section.'
  ])
    ->assertOk()
    ->assertJsonPath('conversation.messages.1.feedback.rating', 'not_helpful')
    ->assertJsonPath(
      'conversation.messages.1.feedback.note',
      'It missed the fee section.'
    );
});

it('rejects an unsupported feedback rating', function () {
  LaravelAiTextAgent::fake(['An answer.']);
  actingAs($this->admin);
  $conversation = startConversation($this->institution);

  $feedbackUrl = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    ['message' => 'What is EduManager?']
  )->json('conversation.messages.1.links.feedback');

  $this->postJson($feedbackUrl, ['rating' => 'sabotage'])
    ->assertStatus(422)
    ->assertJsonValidationErrors('rating');
});

it(
  'does not record feedback against a user message or an unknown message',
  function () {
    LaravelAiTextAgent::fake(['An answer.']);
    actingAs($this->admin);
    $conversation = startConversation($this->institution);

    $sent = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => 'What is EduManager?']
    )->assertOk();
    $userMessageId = $sent->json('conversation.messages.0.id');

    $this->postJson(
      route('institutions.assistant.messages.feedback', [
        $this->institution,
        $conversation,
        $userMessageId
      ]),
      ['rating' => 'helpful']
    )->assertNotFound();

    $this->postJson(
      route('institutions.assistant.messages.feedback', [
        $this->institution,
        $conversation,
        'a-message-that-does-not-exist'
      ]),
      ['rating' => 'helpful']
    )->assertNotFound();
  }
);

it(
  'never lets another user rate or delete a conversation they do not own',
  function () {
    LaravelAiTextAgent::fake(['An answer.']);
    actingAs($this->admin);
    $conversation = startConversation($this->institution);
    $feedbackUrl = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => 'What is EduManager?']
    )->json('conversation.messages.1.links.feedback');

    $otherAdmin = User::factory()
      ->admin($this->institution)
      ->create();
    actingAs($otherAdmin);

    $this->postJson($feedbackUrl, ['rating' => 'helpful'])->assertNotFound();
    $this->deleteJson(
      route('institutions.assistant.conversations.forget', [
        $this->institution,
        $conversation
      ])
    )->assertNotFound();

    expect(
      AiConversation::query()
        ->whereKey($conversation->getKey())
        ->exists()
    )->toBeTrue();
  }
);

it('permanently deletes an owned conversation and its messages', function () {
  LaravelAiTextAgent::fake(['An answer.']);
  actingAs($this->admin);
  $conversation = startConversation($this->institution);

  $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    ['message' => 'What is EduManager?']
  )->assertOk();

  expect(
    AiConversationMessage::query()
      ->where('conversation_id', $conversation->getKey())
      ->count()
  )->toBe(2);

  $this->deleteJson(
    route('institutions.assistant.conversations.forget', [
      $this->institution,
      $conversation
    ])
  )->assertOk();

  expect(
    AiConversation::query()
      ->whereKey($conversation->getKey())
      ->exists()
  )
    ->toBeFalse()
    ->and(
      AiConversationMessage::query()
        ->where('conversation_id', $conversation->getKey())
        ->exists()
    )
    ->toBeFalse();
});

it(
  'keeps archive reversible and separate from permanent deletion',
  function () {
    actingAs($this->admin);
    $conversation = startConversation($this->institution);

    $this->deleteJson(
      route('institutions.assistant.conversations.destroy', [
        $this->institution,
        $conversation
      ])
    )->assertOk();

    expect(
      AiConversation::query()
        ->whereKey($conversation->getKey())
        ->exists()
    )
      ->toBeTrue()
      ->and(
        AiConversation::query()
          ->whereKey($conversation->getKey())
          ->value('archived_at')
      )
      ->not->toBeNull();
  }
);
