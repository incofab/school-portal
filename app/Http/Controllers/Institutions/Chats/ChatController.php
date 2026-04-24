<?php

namespace App\Http\Controllers\Institutions\Chats;

use App\Enums\ChatThreadType;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use function route;

class ChatController extends Controller
{
  public function index(Institution $institution)
  {
    return $this->renderPage($institution);
  }

  public function show(Institution $institution, ChatThread $chatThread)
  {
    abort_unless(
      $chatThread->institution_id === $institution->id,
      404,
      'Chat thread not found'
    );

    abort_unless(
      $chatThread->canBeAccessedBy(currentUser(), currentInstitutionUser()),
      403,
      'You are not allowed to view this chat thread.'
    );

    $chatThread->load('latestMessage');
    $chatThread->markAsRead(currentUser());

    return $this->renderPage($institution, $chatThread);
  }

  public function store(Request $request, Institution $institution)
  {
    $institutionUser = currentInstitutionUser();
    $data = $request->validate([
      'type' => ['required', Rule::in(ChatThreadType::values())],
      'target_user_id' => ['nullable', 'integer'],
      'target_role' => ['nullable', Rule::in([
        InstitutionUserType::Admin->value,
        InstitutionUserType::Teacher->value,
        InstitutionUserType::Accountant->value
      ])],
      'message' => ['required', 'string', 'max:2000']
    ]);

    $type = ChatThreadType::from($data['type']);
    abort_if(
      $type === ChatThreadType::DirectUser &&
        !in_array($institutionUser->role, [
          InstitutionUserType::Guardian,
          InstitutionUserType::Student,
          InstitutionUserType::Alumni
        ]),
      403,
      'Only guardians, students, and alumni can start direct staff chats.'
    );

    $thread = $this->findOrCreateThread(
      $institution,
      currentUser(),
      $institutionUser,
      $type,
      $data
    );

    $message = $thread->messages()->create([
      'institution_id' => $institution->id,
      'sender_user_id' => currentUser()->id,
      'body' => $data['message']
    ]);

    $thread->recordLastMessage($message);
    $thread->markAsRead(currentUser(), $message);

    return $this->ok([
      'thread_id' => $thread->id,
      'message' => 'Chat started successfully.'
    ]);
  }

  private function renderPage(
    Institution $institution,
    ?ChatThread $selectedThread = null
  ) {
    $user = currentUser();
    $institutionUser = currentInstitutionUser();

    $threads = ChatThread::query()
      ->select('chat_threads.*')
      ->visibleTo($institution, $user, $institutionUser)
      ->with([
        'requester:id,first_name,last_name,other_names,photo',
        'requester.institutionUsers' => fn($query) => $query
          ->select('id', 'user_id', 'institution_id', 'role')
          ->where('institution_id', $institution->id),
        'targetUser:id,first_name,last_name,other_names,photo',
        'targetUser.institutionUsers' => fn($query) => $query
          ->select('id', 'user_id', 'institution_id', 'role')
          ->where('institution_id', $institution->id),
        'latestMessage' => fn($query) => $query->select(
          'chat_messages.id',
          'chat_messages.chat_thread_id',
          'chat_messages.sender_user_id',
          'chat_messages.body',
          'chat_messages.created_at'
        ),
        'latestMessage.sender:id,first_name,last_name,other_names,photo',
      ])
      ->selectSub(
        fn($query) => $query
          ->from('chat_thread_reads')
          ->select('chat_thread_reads.last_read_chat_message_id')
          ->whereColumn('chat_thread_reads.chat_thread_id', 'chat_threads.id')
          ->where('chat_thread_reads.user_id', $user->id)
          ->limit(1),
        'current_user_last_read_message_id'
      )
      ->orderByDesc('last_message_at')
      ->orderByDesc('id')
      ->get();

    $activeThread = $selectedThread
      ? ChatThread::query()
        ->select('chat_threads.*')
        ->with([
          'requester:id,first_name,last_name,other_names,photo',
          'requester.institutionUsers' => fn($query) => $query
            ->select('id', 'user_id', 'institution_id', 'role')
            ->where('institution_id', $institution->id),
          'targetUser:id,first_name,last_name,other_names,photo',
          'targetUser.institutionUsers' => fn($query) => $query
            ->select('id', 'user_id', 'institution_id', 'role')
            ->where('institution_id', $institution->id),
          'messages' => fn($query) => $query
            ->select(
              'chat_messages.id',
              'chat_messages.chat_thread_id',
              'chat_messages.sender_user_id',
              'chat_messages.body',
              'chat_messages.created_at'
            )
            ->with([
              'sender:id,first_name,last_name,other_names,photo',
              'sender.institutionUsers' => fn($query) => $query
                ->select('id', 'user_id', 'institution_id', 'role')
                ->where('institution_id', $institution->id)
            ])
            ->latest('id')
            ->limit(100),
        ])
        ->find($selectedThread->id)
      : null;

    if ($activeThread) {
      $activeThread->setRelation(
        'messages',
        $activeThread->messages->sortBy('id')->values()
      );
    }

    return Inertia::render('institutions/chats/index', [
      'threads' => $threads->map(
        fn(ChatThread $thread) => $this->transformThreadSummary(
          $thread,
          $institution,
          $user
        )
      ),
      'activeThread' => $activeThread
        ? $this->transformActiveThread($activeThread, $institution, $user)
        : null,
      'chatComposerOptions' => [
        'canDirectMessageStaff' => in_array($institutionUser->role, [
          InstitutionUserType::Guardian,
          InstitutionUserType::Student,
          InstitutionUserType::Alumni
        ]),
        'institutionTarget' => [
          'label' => 'Institution Admin Desk',
          'description' => 'Reach the institution directly. Only admins attend this inbox.'
        ],
        'roleTargets' => [
          [
            'value' => InstitutionUserType::Admin->value,
            'label' => 'Admin',
            'description' => 'Reach any available admin.'
          ],
          [
            'value' => InstitutionUserType::Teacher->value,
            'label' => 'Teacher',
            'description' => 'Reach the teaching team or any admin.'
          ],
          [
            'value' => InstitutionUserType::Accountant->value,
            'label' => 'Accountant',
            'description' => 'Reach the finance desk or any admin.'
          ]
        ],
        'staffTargets' => InstitutionUser::query()
          ->where('institution_id', $institution->id)
          ->whereIn('role', [
            InstitutionUserType::Admin->value,
            InstitutionUserType::Teacher->value,
            InstitutionUserType::Accountant->value
          ])
          ->where('user_id', '!=', $user->id)
          ->with('user')
          ->get()
          ->map(function (InstitutionUser $institutionUser) {
            return [
              'value' => $institutionUser->user_id,
              'label' => $institutionUser->user?->full_name,
              'description' => ucfirst($institutionUser->role->value),
              'photo_url' => $institutionUser->user?->photo_url
            ];
          })
          ->values()
      ]
    ]);
  }

  private function findOrCreateThread(
    Institution $institution,
    User $requester,
    InstitutionUser $requesterInstitutionUser,
    ChatThreadType $type,
    array $data
  ): ChatThread {
    if ($type === ChatThreadType::Institution) {
      return ChatThread::query()->firstOrCreate([
        'institution_id' => $institution->id,
        'requester_user_id' => $requester->id,
        'type' => $type->value
      ]);
    }

    if ($type === ChatThreadType::Role) {
      return ChatThread::query()->firstOrCreate([
        'institution_id' => $institution->id,
        'requester_user_id' => $requester->id,
        'type' => $type->value,
        'target_role' => $data['target_role']
      ]);
    }

    $targetInstitutionUser = InstitutionUser::query()
      ->where('institution_id', $institution->id)
      ->where('user_id', $data['target_user_id'])
      ->whereIn('role', [
        InstitutionUserType::Admin->value,
        InstitutionUserType::Teacher->value,
        InstitutionUserType::Accountant->value
      ])
      ->with('user')
      ->first();

    abort_unless(
      $targetInstitutionUser,
      422,
      'Please select a valid staff member.'
    );

    abort_if(
      $targetInstitutionUser->user_id === $requester->id,
      422,
      'You cannot start a chat with yourself.'
    );

    abort_if(
      !in_array($requesterInstitutionUser->role, [
        InstitutionUserType::Guardian,
        InstitutionUserType::Student,
        InstitutionUserType::Alumni
      ]),
      403,
      'You are not allowed to start a direct staff chat.'
    );

    return ChatThread::query()->firstOrCreate([
      'institution_id' => $institution->id,
      'requester_user_id' => $requester->id,
      'type' => $type->value,
      'target_user_id' => $targetInstitutionUser->user_id
    ]);
  }

  private function transformThreadSummary(
    ChatThread $thread,
    Institution $institution,
    User $viewer
  ): array {
    [$title, $subtitle, $photoUrl] = $this->describeThread(
      $thread,
      $institution,
      $viewer
    );
    $latestMessage = $thread->latestMessage;
    $lastReadMessageId = (int) ($thread->current_user_last_read_message_id ?? 0);

    return [
      'id' => $thread->id,
      'type' => $thread->type->value,
      'title' => $title,
      'subtitle' => $subtitle,
      'photo_url' => $photoUrl,
      'last_message_preview' =>
        $thread->last_message_preview ??
        ($latestMessage ? str($latestMessage->body)->limit(160)->value() : null),
      'last_message_at' => $thread->last_message_at,
      'has_unread' => $latestMessage
        ? $latestMessage->sender_user_id !== $viewer->id &&
          $lastReadMessageId < $latestMessage->id
        : false
    ];
  }

  private function transformActiveThread(
    ChatThread $thread,
    Institution $institution,
    User $viewer
  ): array {
    [$title, $subtitle, $photoUrl] = $this->describeThread(
      $thread,
      $institution,
      $viewer
    );

    return [
      'id' => $thread->id,
      'type' => $thread->type->value,
      'title' => $title,
      'subtitle' => $subtitle,
      'photo_url' => $photoUrl,
      'profile_url' => $this->resolveThreadProfileUrl(
        $thread,
        $institution,
        $viewer
      ),
      'messages' => $thread->messages->map(
        fn(ChatMessage $message) => [
          'id' => $message->id,
          'body' => $message->body,
          'created_at' => $message->created_at,
          'is_mine' => $message->sender_user_id === $viewer->id,
          'sender' => [
            'id' => $message->sender?->id,
            'full_name' => $message->sender?->full_name,
            'photo_url' => $message->sender?->photo_url,
            'role' => $message
              ->sender?->institutionUsers
              ?->firstWhere('institution_id', $institution->id)
              ?->role?->value
          ]
        ]
      )->values()
    ];
  }

  private function resolveThreadProfileUrl(
    ChatThread $thread,
    Institution $institution,
    User $viewer
  ): ?string {
    $profileUser = null;

    if ($thread->type === ChatThreadType::DirectUser) {
      $profileUser = $thread->requester_user_id === $viewer->id
        ? $thread->targetUser
        : $thread->requester;
    } elseif (
      $thread->type === ChatThreadType::Institution &&
      $thread->requester_user_id !== $viewer->id
    ) {
      $profileUser = $thread->requester;
    } elseif (
      $thread->type === ChatThreadType::Role &&
      $thread->requester_user_id !== $viewer->id
    ) {
      $profileUser = $thread->requester;
    }

    if (!$profileUser) {
      return null;
    }

    return route('institutions.users.profile', [
      $institution,
      $profileUser
    ]);
  }

  private function describeThread(
    ChatThread $thread,
    Institution $institution,
    User $viewer
  ): array {
    $requesterRole = $thread
      ->requester
      ?->institutionUsers
      ?->firstWhere('institution_id', $institution->id)
      ?->role
      ?->value;
    $targetRole = $thread
      ->targetUser
      ?->institutionUsers
      ?->firstWhere('institution_id', $institution->id)
      ?->role
      ?->value;

    if ($thread->type === ChatThreadType::DirectUser) {
      $counterparty = $thread->requester_user_id === $viewer->id
        ? $thread->targetUser
        : $thread->requester;

      $counterpartyRole = $thread->requester_user_id === $viewer->id
        ? $targetRole
        : $requesterRole;

      return [
        $counterparty?->full_name ?? 'Staff Member',
        $counterpartyRole ? ucfirst($counterpartyRole) : 'Direct staff chat',
        $counterparty?->photo_url
      ];
    }

    if ($thread->type === ChatThreadType::Institution) {
      if ($thread->requester_user_id === $viewer->id) {
        return [
          'Institution Admin Desk',
          'Admin-only institution inbox',
          null
        ];
      }

      return [
        $thread->requester?->full_name ?? 'Institution User',
        $requesterRole ? ucfirst($requesterRole) : 'Institution user',
        $thread->requester?->photo_url
      ];
    }

    if ($thread->requester_user_id === $viewer->id) {
      return [
        ucfirst($thread->target_role) . ' Desk',
        'Any matching staff member or admin can reply',
        null
      ];
    }

    return [
      $thread->requester?->full_name ?? 'Institution User',
      ucfirst($thread->target_role) . ' conversation',
      $thread->requester?->photo_url
    ];
  }
}
