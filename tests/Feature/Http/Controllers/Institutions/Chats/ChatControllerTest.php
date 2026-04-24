<?php

use App\Enums\ChatThreadType;
use App\Enums\InstitutionUserType;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Institution;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->teacher = User::factory()->teacher($this->institution)->create();
  $this->accountant = User::factory()
    ->accountant($this->institution)
    ->create();
  $this->guardian = User::factory()->guardian($this->institution)->create();
  $this->student = User::factory()->student($this->institution)->create();
  $this->alumni = User::factory()->create();
  $this->alumni->institutionUsers()->create([
    'institution_id' => $this->institution->id,
    'role' => InstitutionUserType::Alumni
  ]);
});

it('lets a student start a direct chat with a staff member', function () {
  actingAs($this->student)
    ->post(route('institutions.chats.store', $this->institution), [
      'type' => ChatThreadType::DirectUser->value,
      'target_user_id' => $this->teacher->id,
      'message' => 'Hello teacher, I need help.'
    ])
    ->assertOk()
    ->assertJsonPath('message', 'Chat started successfully.');

  $thread = ChatThread::query()->first();

  expect($thread->type)->toBe(ChatThreadType::DirectUser);
  expect($thread->requester_user_id)->toBe($this->student->id);
  expect($thread->target_user_id)->toBe($this->teacher->id);

  assertDatabaseHas('chat_messages', [
    'chat_thread_id' => $thread->id,
    'sender_user_id' => $this->student->id,
    'body' => 'Hello teacher, I need help.'
  ]);
});

it('lets any institution user start institution and role chats', function () {
  actingAs($this->guardian)
    ->post(route('institutions.chats.store', $this->institution), [
      'type' => ChatThreadType::Institution->value,
      'message' => 'I need help from the school.'
    ])
    ->assertOk();

  actingAs($this->guardian)
    ->post(route('institutions.chats.store', $this->institution), [
      'type' => ChatThreadType::Role->value,
      'target_role' => InstitutionUserType::Accountant->value,
      'message' => 'Please check my payment issue.'
    ])
    ->assertOk();

  assertDatabaseHas('chat_threads', [
    'institution_id' => $this->institution->id,
    'requester_user_id' => $this->guardian->id,
    'type' => ChatThreadType::Institution->value
  ]);

  assertDatabaseHas('chat_threads', [
    'institution_id' => $this->institution->id,
    'requester_user_id' => $this->guardian->id,
    'type' => ChatThreadType::Role->value,
    'target_role' => InstitutionUserType::Accountant->value
  ]);
});

it('prevents staff-only direct chat creation by non eligible users', function () {
  actingAs($this->teacher)
    ->post(route('institutions.chats.store', $this->institution), [
      'type' => ChatThreadType::DirectUser->value,
      'target_user_id' => $this->accountant->id,
      'message' => 'Hello accountant.'
    ])
    ->assertForbidden();
});

it('shows only accessible chat threads in the inbox', function () {
  $directThread = ChatThread::factory()
    ->institution($this->institution)
    ->create([
      'requester_user_id' => $this->student->id,
      'target_user_id' => $this->teacher->id,
      'type' => ChatThreadType::DirectUser->value
    ]);
  ChatMessage::factory()->create([
    'institution_id' => $this->institution->id,
    'chat_thread_id' => $directThread->id,
    'sender_user_id' => $this->student->id,
    'body' => 'Need help with class work'
  ]);
  $directThread->update(['last_message_at' => now()]);

  $institutionThread = ChatThread::factory()
    ->institution($this->institution)
    ->create([
      'requester_user_id' => $this->guardian->id,
      'target_user_id' => null,
      'type' => ChatThreadType::Institution->value
    ]);
  ChatMessage::factory()->create([
    'institution_id' => $this->institution->id,
    'chat_thread_id' => $institutionThread->id,
    'sender_user_id' => $this->guardian->id,
    'body' => 'Need admin assistance'
  ]);
  $institutionThread->update(['last_message_at' => now()]);

  actingAs($this->teacher)
    ->get(route('institutions.chats.index', $this->institution))
    ->assertInertia(function (AssertableInertia $page) use ($directThread) {
      $page
        ->component('institutions/chats/index')
        ->has('threads', 1)
        ->where('threads.0.id', $directThread->id);
    });

  actingAs($this->admin)
    ->get(route('institutions.chats.index', $this->institution))
    ->assertInertia(function (AssertableInertia $page) use ($institutionThread) {
      $page
        ->component('institutions/chats/index')
        ->has('threads', 1)
        ->where('threads.0.id', $institutionThread->id);
    });
});

it('allows only the intended staff member to view a direct chat thread', function () {
  $thread = ChatThread::factory()
    ->institution($this->institution)
    ->create([
      'requester_user_id' => $this->student->id,
      'target_user_id' => $this->teacher->id,
      'type' => ChatThreadType::DirectUser->value
    ]);
  ChatMessage::factory()->create([
    'institution_id' => $this->institution->id,
    'chat_thread_id' => $thread->id,
    'sender_user_id' => $this->student->id,
    'body' => 'Need help'
  ]);
  $thread->update(['last_message_at' => now()]);

  actingAs($this->teacher)
    ->get(route('institutions.chats.show', [$this->institution, $thread]))
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/chats/index')
        ->where('activeThread.id', $thread->id)
        ->where(
          'activeThread.profile_url',
          route('institutions.users.profile', [$this->institution, $this->student])
        )
    );

  actingAs($this->accountant)
    ->get(route('institutions.chats.show', [$this->institution, $thread]))
    ->assertForbidden();
});

it('exposes a clickable profile link only for user-backed active threads', function () {
  $institutionThread = ChatThread::factory()
    ->institution($this->institution)
    ->create([
      'requester_user_id' => $this->guardian->id,
      'target_user_id' => null,
      'type' => ChatThreadType::Institution->value
    ]);

  ChatMessage::factory()->create([
    'institution_id' => $this->institution->id,
    'chat_thread_id' => $institutionThread->id,
    'sender_user_id' => $this->guardian->id,
    'body' => 'Need admin assistance'
  ]);

  actingAs($this->guardian)
    ->get(route('institutions.chats.show', [$this->institution, $institutionThread]))
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/chats/index')
        ->where('activeThread.profile_url', null)
    );

  actingAs($this->admin)
    ->get(route('institutions.chats.show', [$this->institution, $institutionThread]))
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/chats/index')
        ->where(
          'activeThread.profile_url',
          route('institutions.users.profile', [$this->institution, $this->guardian])
        )
    );
});

it('lets matching role staff and admin reply to role threads', function () {
  $thread = ChatThread::factory()
    ->institution($this->institution)
    ->create([
      'requester_user_id' => $this->guardian->id,
      'target_user_id' => null,
      'type' => ChatThreadType::Role->value,
      'target_role' => InstitutionUserType::Accountant->value
    ]);

  actingAs($this->accountant)
    ->post(
      route('institutions.chats.messages.store', [$this->institution, $thread]),
      ['message' => 'We are reviewing this now.']
    )
    ->assertOk();

  actingAs($this->admin)
    ->post(
      route('institutions.chats.messages.store', [$this->institution, $thread]),
      ['message' => 'Admin follow-up.']
    )
    ->assertOk();

  actingAs($this->teacher)
    ->post(
      route('institutions.chats.messages.store', [$this->institution, $thread]),
      ['message' => 'I should not reply here.']
    )
    ->assertForbidden();

  assertDatabaseHas('chat_messages', [
    'chat_thread_id' => $thread->id,
    'sender_user_id' => $this->accountant->id,
    'body' => 'We are reviewing this now.'
  ]);

  assertDatabaseHas('chat_messages', [
    'chat_thread_id' => $thread->id,
    'sender_user_id' => $this->admin->id,
    'body' => 'Admin follow-up.'
  ]);
});
