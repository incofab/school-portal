<?php

use App\Contracts\AI\AssistantTextGenerator;
use App\DTO\AI\AssistantGenerationResult;
use App\Http\Middleware\EncryptCookies;
use App\Models\AiConversation;
use App\Models\Institution;
use App\Models\User;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantConversationMemory;
use App\Services\AI\AssistantConversationService;
use App\Services\AI\LaravelAiTextAgent;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
});

it('renders the public and institution assistant surfaces', function () {
    $this->get(route('assistant.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('assistant/index')
                ->where('actor.is_guest', true)
                ->has('endpoints.create')
        );

    actingAs($this->admin)
        ->get(route('institutions.assistant.index', $this->institution))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('institutions/assistant/index')
                ->where('actor.is_guest', false)
                ->has('endpoints.create')
        );
});

it('allows a guest to start an open-ended assistant conversation', function () {
    $fake = LaravelAiTextAgent::fake([
        'You can ask me anything about EduManager.'
    ]);

    $create = $this->postJson(route('assistant.conversations.store'));

    $create
        ->assertCreated()
        ->assertJsonPath('conversation.title', 'New conversation')
        ->assertCookie(config('ai.assistant.guest_cookie'));

    $conversationId = $create->json('conversation.id');
    expect(AiConversation::query()->find($conversationId))->not->toBeNull();
    $guestCookie = $create->getCookie(config('ai.assistant.guest_cookie'));
    expect(
        app(AssistantConversationService::class)
            ->findFor(
                $conversationId,
                app(AssistantContextResolver::class)->resolve(),
                $guestCookie->getValue()
            )
            ->getKey()
    )->toBe($conversationId);

    $response = $this->withCredentials()
        ->withoutMiddleware(EncryptCookies::class)
        ->withUnencryptedCookie(
            config('ai.assistant.guest_cookie'),
            $guestCookie->getValue()
        )
        ->postJson(route('assistant.messages.store', [$conversationId]), [
            'message' => 'I am not using a predefined question. Can you help me understand EduManager?',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath(
            'conversation.messages.0.content',
            'I am not using a predefined question. Can you help me understand EduManager?'
        )
        ->assertJsonPath(
            'conversation.messages.1.content',
            'You can ask me anything about EduManager.'
        );

    assertDatabaseHas('agent_conversation_messages', [
        'conversation_id' => $conversationId,
        'role' => 'assistant',
        'content' => 'You can ask me anything about EduManager.',
    ]);

    LaravelAiTextAgent::assertPromptedTimes(1);
    LaravelAiTextAgent::assertPrompted(
        fn ($request) => expect($request->prompt)
            ->toContain('I am not using a predefined question')
            ->not->toContain($this->institution->name)
    );
});

it('explains action-shaped questions to guests instead of trying to prepare an action', function () {
    $fake = LaravelAiTextAgent::fake([
        'To create a class, sign in to your institution workspace and open Classes.'
    ]);

    $create = $this->postJson(route('assistant.conversations.store'));
    $guestCookie = $create->getCookie(config('ai.assistant.guest_cookie'));

    $response = $this->withCredentials()
        ->withoutMiddleware(EncryptCookies::class)
        ->withUnencryptedCookie(
            config('ai.assistant.guest_cookie'),
            $guestCookie->getValue()
        )
        ->postJson(route('assistant.messages.store', [$create->json('conversation.id')]), [
            'message' => 'How do I create a class on EduManager?',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath(
            'conversation.messages.1.content',
            'To create a class, sign in to your institution workspace and open Classes.'
        )
        ->assertJsonPath('conversation.messages.1.action', null);

    LaravelAiTextAgent::assertPromptedTimes(1);
});

it(
    'keeps authenticated conversations scoped to the current institution and user',
        function () {
            LaravelAiTextAgent::fake([
                'This is a private institution response.'
        ]);

        $create = actingAs($this->admin)->postJson(
            route('institutions.assistant.conversations.store', $this->institution)
        );

        $conversationId = $create->json('conversation.id');

        actingAs($this->admin)
            ->postJson(
                route('institutions.assistant.messages.store', [
                    $this->institution,
                    $conversationId,
                ]),
                ['message' => 'Show me what you know about my school.']
            )
            ->assertOk()
            ->assertJsonPath(
                'conversation.messages.1.content',
                'This is a private institution response.'
            );

        $otherUser = User::factory()
            ->teacher($this->institution)
            ->create();

        actingAs($otherUser)
            ->getJson(
                route('institutions.assistant.conversations.show', [
                    $this->institution,
                    $conversationId,
                ])
            )
            ->assertNotFound();

        assertDatabaseHas('agent_conversations', [
            'id' => $conversationId,
            'participant_id' => $this->admin->id,
            'institution_id' => $this->institution->id,
        ]);
    }
);

it('does not expose authenticated conversations to guests', function () {
    $conversation = app(AssistantConversationService::class)->create(
        app(AssistantContextResolver::class)->resolve($this->institution),
        null
    );

    $this->getJson(
        route('assistant.conversations.show', [$conversation])
    )->assertNotFound();
});

it('rejects messages above the configured input limit', function () {
    $create = actingAs($this->admin)->postJson(
        route('institutions.assistant.conversations.store', $this->institution)
    );

    actingAs($this->admin)
        ->postJson(
            route('institutions.assistant.messages.store', [
                $this->institution,
                $create->json('conversation.id'),
            ]),
            ['message' => str_repeat('a', config('ai.assistant.max_input_chars') + 1)]
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('returns a safe response when the provider is unavailable', function () {
    app()->instance(
        AssistantTextGenerator::class,
        new class implements AssistantTextGenerator
        {
            public function generate(
                string $systemPrompt,
                string $prompt,
                array $options = []
            ): AssistantGenerationResult {
                throw new RuntimeException('Provider credentials must not leak.');
            }
        }
    );

    $create = actingAs($this->admin)->postJson(
        route('institutions.assistant.conversations.store', $this->institution)
    );

    actingAs($this->admin)
        ->postJson(
            route('institutions.assistant.messages.store', [
                $this->institution,
                $create->json('conversation.id'),
            ]),
            ['message' => 'Please answer this safely.']
        )
        ->assertStatus(503)
        ->assertJsonPath(
            'message',
            'The assistant is temporarily unavailable. Please try again shortly.'
        )
        ->assertJsonMissing(['message' => 'Provider credentials must not leak.']);
});

it('archives only a conversation owned by the current actor', function () {
    $create = actingAs($this->admin)->postJson(
        route('institutions.assistant.conversations.store', $this->institution)
    );
    $conversationId = $create->json('conversation.id');

    actingAs($this->admin)
        ->deleteJson(
            route('institutions.assistant.conversations.destroy', [
                $this->institution,
                $conversationId,
            ])
        )
        ->assertOk()
        ->assertJsonPath('message', 'Conversation archived.');

    expect(
        AiConversation::query()->find($conversationId)->archived_at
    )->not->toBeNull();

    actingAs($this->admin)
        ->getJson(
            route('institutions.assistant.conversations.show', [
                $this->institution,
                $conversationId,
            ])
        )
        ->assertNotFound();
});

it(
    'asks for academic context before handling an ambiguous data request',
    function () {
        $fake = LaravelAiTextAgent::fake([
            'This provider response should not be used.'
        ]);

        $create = actingAs($this->admin)->postJson(
            route('institutions.assistant.conversations.store', $this->institution)
        );
        $conversationId = $create->json('conversation.id');

        $response = actingAs($this->admin)->postJson(
            route('institutions.assistant.messages.store', [
                $this->institution,
                $conversationId,
            ]),
            ['message' => 'Show me the results.']
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'conversation.messages.1.clarification.question',
                'Which academic session and term should I use? For example: “2025/2026, First Term.”'
            )
            ->assertJsonPath('conversation.memory.entities', []);

        LaravelAiTextAgent::assertPromptedTimes(0);
    }
);

it('carries context into a follow-up after clarification', function () {
    config(['ai.assistant.summary_after_messages' => 2]);

    $fake = LaravelAiTextAgent::fake([
        'I can help with that result request.',
    ]);

    $create = actingAs($this->admin)->postJson(
        route('institutions.assistant.conversations.store', $this->institution)
    );
    $conversationId = $create->json('conversation.id');

    actingAs($this->admin)
        ->postJson(
            route('institutions.assistant.messages.store', [
                $this->institution,
                $conversationId,
            ]),
            ['message' => 'Show me the results.']
        )
        ->assertOk();

    actingAs($this->admin)
        ->postJson(
            route('institutions.assistant.messages.store', [
                $this->institution,
                $conversationId,
            ]),
            ['message' => '2025/2026, First Term.']
        )
        ->assertOk()
        ->assertJsonPath(
            'conversation.memory.entities.academic_session',
            '2025/2026'
        )
        ->assertJsonPath('conversation.memory.entities.term', 'First Term');

    $conversation = AiConversation::query()->find($conversationId);
    expect($conversation->summary)->toContain('Show me the results.');

    LaravelAiTextAgent::assertPromptedTimes(1);
    LaravelAiTextAgent::assertPrompted(
        fn ($request) => expect($request->prompt)
            ->toContain('Show me the results.')
            ->toContain('academic_session: 2025/2026')
            ->toContain('term: First Term')
    );
});

it(
    'keeps memory bounded and records corrections and topic changes',
    function () {
        config([
            'ai.assistant.summary_after_messages' => 2,
            'ai.assistant.max_context_chars' => 1000,
        ]);

        $conversation = app(AssistantConversationService::class)->create(
            app(AssistantContextResolver::class)->resolve($this->institution),
            null
        );
        $memory = app(AssistantConversationMemory::class);

        $first = $memory->interpret(
            $conversation,
            'Show results for 2024/2025, First Term.'
        );
        $memory->apply($conversation, $first);
        expect($conversation->fresh()->entities)->toMatchArray([
            'academic_session' => '2024/2025',
            'term' => 'First Term',
        ]);

        $correction = $memory->interpret(
            $conversation->fresh(),
            'Actually, use 2025/2026, Second Term.'
        );
        $memory->apply($conversation, $correction);
        expect($correction['correction'])
            ->toBeTrue()
            ->and($conversation->fresh()->entities)
            ->toMatchArray([
                'academic_session' => '2025/2026',
                'term' => 'Second Term',
            ]);

        $topicChange = $memory->interpret(
            $conversation->fresh(),
            'Show me the fee balance.'
        );
        $memory->apply($conversation, $topicChange);
        expect($topicChange['topic_changed'])
            ->toBeTrue()
            ->and($conversation->fresh()->topic)
            ->toBe('fees')
            ->and($conversation->fresh()->entities)
            ->toBe([]);
    }
);

it('allows an owner to rename a conversation', function () {
    $create = actingAs($this->admin)->postJson(
        route('institutions.assistant.conversations.store', $this->institution)
    );
    $conversationId = $create->json('conversation.id');

    actingAs($this->admin)
        ->patchJson(
            route('institutions.assistant.conversations.update', [
                $this->institution,
                $conversationId,
            ]),
            ['title' => 'Term results review']
        )
        ->assertOk()
        ->assertJsonPath('conversation.title', 'Term results review');

    expect(AiConversation::query()->find($conversationId)->title)->toBe(
        'Term results review'
    );
});
