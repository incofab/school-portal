<?php

namespace App\Http\Controllers\AI;

use App\DTO\AI\AssistantToolResult;
use App\Exceptions\AssistantRateLimitExceededException;
use App\Exceptions\AssistantUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\Institution;
use App\Services\AI\AssistantActionService;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantConversationService;
use App\Services\AI\AssistantSuggestionProvider;
use App\Services\AI\AssistantTelemetry;
use App\Services\AI\AssistantUsageGuard;
use App\Services\AI\EduManagerAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AssistantController extends Controller
{
  public function __construct(
    private readonly AssistantContextResolver $contextResolver,
    private readonly AssistantConversationService $conversations,
    private readonly EduManagerAssistant $assistant,
    private readonly AssistantActionService $actions,
    private readonly AssistantSuggestionProvider $suggestions,
    private readonly AssistantTelemetry $telemetry,
    private readonly AssistantUsageGuard $usageGuard
  ) {
  }

  public function index(
    Request $request,
    ?Institution $institution = null
  ): InertiaResponse|SymfonyResponse {
    $context = $this->context($institution);
    $guestToken = $this->guestToken($request, $context->isGuest);

    $this->queueGuestCookie($context->isGuest ? $guestToken : null);

    return Inertia::render(
      $institution ? 'institutions/assistant/index' : 'assistant/index',
      [
        'conversations' => collect(
          $this->conversations->listFor($context, $guestToken)
        )
          ->map(
            fn(array $conversation) => [
              ...$conversation,
              'links' => $this->linksForId($conversation['id'], $institution)
            ]
          )
          ->values()
          ->all(),
        'conversation' => null,
        'actor' => [
          'is_guest' => $context->isGuest,
          'role' => $context->role(),
          'institution_name' => $context->institution?->name,
          'can_run_actions' =>
            $institution !== null &&
            (bool) $context->institutionUser?->isActive()
        ],
        'suggestions' => $this->suggestions->for($context),
        'endpoints' => [
          'create' => $this->route('conversations.store', $institution)
        ]
      ]
    );
  }

  public function storeConversation(
    Request $request,
    ?Institution $institution = null
  ): JsonResponse|Response {
    $context = $this->context($institution);
    $guestToken = $this->guestToken($request, $context->isGuest, true);
    $conversation = $this->conversations->create($context, $guestToken);

    $this->queueGuestCookie($context->isGuest ? $guestToken : null);

    return $this->ok(
      [
        'conversation' => $this->details($conversation, $institution)
      ],
      201
    );
  }

  public function show(
    Request $request,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->showConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, null),
      null
    );
  }

  public function showForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->showConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, $institution),
      $institution
    );
  }

  private function showConversation(
    Request $request,
    AiConversation $aiConversation,
    ?Institution $institution
  ): JsonResponse|Response {
    $context = $this->context($institution);
    $guestToken = $this->guestToken($request, $context->isGuest);
    $conversation = $this->conversations->findFor(
      (string) $aiConversation->getKey(),
      $context,
      $guestToken
    );

    return $this->ok([
      'conversation' => $this->details($conversation, $institution)
    ]);
  }

  public function sendMessage(
    Request $request,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->sendConversationMessage(
      $request,
      $this->conversationForRequest($request, $aiConversation, null),
      null
    );
  }

  public function sendMessageForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->sendConversationMessage(
      $request,
      $this->conversationForRequest($request, $aiConversation, $institution),
      $institution
    );
  }

  public function confirmActionForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation,
    string $execution
  ): JsonResponse|Response {
    $context = $this->context($institution);
    $conversation = $this->conversations->findFor(
      $aiConversation,
      $context,
      null
    );
    $data = $request->validate([
      'confirmation_token' => ['required', 'string', 'max:128']
    ]);
    $result = $this->actions->confirm(
      $conversation,
      $execution,
      $data['confirmation_token'],
      $context
    );

    return $this->actionResponse($result, $conversation, $institution);
  }

  public function cancelActionForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation,
    string $execution
  ): JsonResponse|Response {
    $context = $this->context($institution);
    $conversation = $this->conversations->findFor(
      $aiConversation,
      $context,
      null
    );
    $data = $request->validate([
      'confirmation_token' => ['required', 'string', 'max:128']
    ]);
    $result = $this->actions->cancel(
      $conversation,
      $execution,
      $data['confirmation_token'],
      $context
    );

    return $this->actionResponse($result, $conversation, $institution);
  }

  public function updateConversation(
    Request $request,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->renameConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, null),
      null
    );
  }

  public function updateConversationForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->renameConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, $institution),
      $institution
    );
  }

  private function renameConversation(
    Request $request,
    AiConversation $aiConversation,
    ?Institution $institution
  ): JsonResponse|Response {
    $data = $request->validate([
      'title' => ['required', 'string', 'max:100']
    ]);
    $conversation = $this->conversations->rename(
      $aiConversation,
      $data['title']
    );

    return $this->ok([
      'conversation' => $this->details($conversation, $institution)
    ]);
  }

  private function sendConversationMessage(
    Request $request,
    AiConversation $aiConversation,
    ?Institution $institution
  ): JsonResponse|Response {
    $context = $this->context($institution);
    $guestToken = $this->guestToken($request, $context->isGuest);
    $conversation = $this->conversations->findFor(
      (string) $aiConversation->getKey(),
      $context,
      $guestToken
    );

    $data = $request->validate([
      'message' => [
        'required',
        'string',
        'max:' . config('ai.assistant.max_input_chars', 4000)
      ]
    ]);

    try {
      $this->usageGuard->check($request, $context);
      $conversation = $this->assistant->reply(
        $conversation,
        $data['message'],
        $context
      );
    } catch (AssistantUnavailableException $exception) {
      return response()->json(
        [
          'ok' => false,
          'message' => $exception->getMessage()
        ],
        503
      );
    } catch (AssistantRateLimitExceededException $exception) {
      return response()
        ->json(
          [
            'ok' => false,
            'message' => $exception->getMessage(),
            'retry_after' => $exception->retryAfter
          ],
          429
        )
        ->header('Retry-After', (string) $exception->retryAfter);
    }

    return $this->ok([
      'conversation' => $this->details($conversation, $institution)
    ]);
  }

  public function destroy(
    Request $request,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->archiveConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, null),
      null
    );
  }

  public function destroyForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->archiveConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, $institution),
      $institution
    );
  }

  public function forget(
    Request $request,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->deleteConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, null)
    );
  }

  public function forgetForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation
  ): JsonResponse|Response {
    return $this->deleteConversation(
      $request,
      $this->conversationForRequest($request, $aiConversation, $institution)
    );
  }

  private function deleteConversation(
    Request $request,
    AiConversation $aiConversation
  ): JsonResponse|Response {
    $this->conversations->delete($aiConversation);

    return $this->ok(['message' => 'Conversation deleted.']);
  }

  public function storeFeedback(
    Request $request,
    string $aiConversation,
    string $message
  ): JsonResponse|Response {
    return $this->recordFeedback(
      $request,
      $this->conversationForRequest($request, $aiConversation, null),
      $message,
      null
    );
  }

  public function storeFeedbackForInstitution(
    Request $request,
    Institution $institution,
    string $aiConversation,
    string $message
  ): JsonResponse|Response {
    return $this->recordFeedback(
      $request,
      $this->conversationForRequest($request, $aiConversation, $institution),
      $message,
      $institution
    );
  }

  private function recordFeedback(
    Request $request,
    AiConversation $aiConversation,
    string $messageId,
    ?Institution $institution
  ): JsonResponse|Response {
    $data = $request->validate([
      'rating' => ['required', 'in:helpful,not_helpful'],
      'note' => ['nullable', 'string', 'max:500']
    ]);

    $recorded = $this->conversations->recordFeedback(
      $aiConversation,
      $messageId,
      $data['rating'],
      $data['note'] ?? null
    );

    if (!$recorded) {
      return response()->json(
        ['ok' => false, 'message' => 'That message could not be found.'],
        404
      );
    }

    $this->telemetry->recordFeedback(
      (string) $recorded->getKey(),
      $data['rating']
    );

    return $this->ok([
      'conversation' => $this->details($aiConversation->fresh(), $institution)
    ]);
  }

  private function archiveConversation(
    Request $request,
    AiConversation $aiConversation,
    ?Institution $institution
  ): JsonResponse|Response {
    $context = $this->context($institution);
    $guestToken = $this->guestToken($request, $context->isGuest);
    $conversation = $this->conversations->findFor(
      (string) $aiConversation->getKey(),
      $context,
      $guestToken
    );

    $conversation->archive();

    return $this->ok(['message' => 'Conversation archived.']);
  }

  private function context(?Institution $institution)
  {
    $context = $this->contextResolver->resolve($institution);

    abort_unless(
      !$context->isGuest || config('ai.assistant.guest_access', true),
      403,
      'The public assistant is currently unavailable.'
    );

    return $context;
  }

  private function conversationForRequest(
    Request $request,
    string $conversationId,
    ?Institution $institution
  ): AiConversation {
    $context = $this->context($institution);

    return $this->conversations->findFor(
      $conversationId,
      $context,
      $this->guestToken($request, $context->isGuest)
    );
  }

  private function guestToken(
    Request $request,
    bool $isGuest,
    bool $create = false
  ): ?string {
    if (!$isGuest) {
      return null;
    }

    $token = $request->cookie(
      config('ai.assistant.guest_cookie', 'edumanager_ai_guest')
    );

    return $token ?: ($create ? Str::random(64) : null);
  }

  private function details(
    AiConversation $conversation,
    ?Institution $institution
  ): array {
    $details = $this->conversations->details($conversation);
    $details['messages'] = collect($details['messages'])
      ->map(
        fn(array $message) => $this->decorateMessage(
          $message,
          $conversation,
          $institution
        )
      )
      ->all();

    return [...$details, 'links' => $this->links($conversation, $institution)];
  }

  /**
   * Attach the endpoints a message's own controls need: feedback for every
   * assistant reply, and confirm/cancel for a prepared institution action.
   */
  private function decorateMessage(
    array $message,
    AiConversation $conversation,
    ?Institution $institution
  ): array {
    $conversationParams = $institution
      ? [$institution, $conversation->getKey()]
      : [$conversation->getKey()];

    if ($message['role'] === 'assistant') {
      $message['links'] = [
        'feedback' => $this->route('messages.feedback', $institution, [
          ...$conversationParams,
          $message['id']
        ])
      ];
    }

    $executionId = $message['action']['id'] ?? null;
    if ($institution && $executionId) {
      $routeParameters = [...$conversationParams, $executionId];
      $message['action'] = [
        ...$message['action'],
        'links' => [
          'confirm' => route(
            'institutions.assistant.actions.confirm',
            $routeParameters
          ),
          'cancel' => route(
            'institutions.assistant.actions.cancel',
            $routeParameters
          )
        ]
      ];
    }

    return $message;
  }

  private function actionResponse(
    AssistantToolResult $result,
    AiConversation $conversation,
    Institution $institution
  ): JsonResponse|Response {
    return response()->json(
      [
        'ok' => $result->ok,
        'message' => $result->message,
        'conversation' => $this->details($conversation->fresh(), $institution)
      ],
      $result->ok ? 200 : 422
    );
  }

  private function links(
    AiConversation $conversation,
    ?Institution $institution
  ): array {
    return $this->linksForId((string) $conversation->getKey(), $institution);
  }

  private function linksForId(
    string $conversationId,
    ?Institution $institution
  ): array {
    $routeParams = $institution
      ? [$institution, $conversationId]
      : [$conversationId];

    return [
      'self' => $this->route('conversations.show', $institution, $routeParams),
      'messages' => $this->route('messages.store', $institution, $routeParams),
      'update' => $this->route(
        'conversations.update',
        $institution,
        $routeParams
      ),
      'archive' => $this->route(
        'conversations.destroy',
        $institution,
        $routeParams
      ),
      'delete' => $this->route(
        'conversations.forget',
        $institution,
        $routeParams
      )
    ];
  }

  private function route(
    string $suffix,
    ?Institution $institution,
    ?array $parameters = null
  ): string {
    $name = $institution
      ? "institutions.assistant.{$suffix}"
      : "assistant.{$suffix}";

    return route($name, $parameters ?? ($institution ? [$institution] : []));
  }

  private function queueGuestCookie(?string $guestToken): void
  {
    if (!$guestToken) {
      return;
    }

    Cookie::queue(
      cookie(
        config('ai.assistant.guest_cookie', 'edumanager_ai_guest'),
        $guestToken,
        config('ai.assistant.guest_cookie_minutes', 43200),
        null,
        null,
        null,
        true,
        false,
        'lax'
      )
    );
  }
}
