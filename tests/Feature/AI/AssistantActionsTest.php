<?php

use App\Models\ActivityLog;
use App\Models\AssistantActionExecution;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InternalNotification;
use App\Models\User;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantConversationService;
use App\Services\AI\AssistantActionPlanner;

use function Pest\Laravel\actingAs;

/**
 * Return the action attached to the most recent assistant message so tests do
 * not depend on the absolute position of a message within the conversation.
 */
function latestAssistantAction($response): ?array
{
  return collect($response->json('conversation.messages'))
    ->reverse()
    ->pluck('action')
    ->filter()
    ->first();
}

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  actingAs($this->admin);
});

it(
  'does not prepare a mutation for explanatory or explicitly declined wording',
  function () {
    $context = app(AssistantContextResolver::class)->resolve(
      $this->institution
    );
    $planner = app(AssistantActionPlanner::class);

    expect($planner->plan('How do I notify parents about an event?', $context))
      ->toBeNull()
      ->and($planner->plan("Don't create a new subject today.", $context))
      ->toBeNull();
  }
);

it(
  'prepares a class action without mutating state until confirmation',
  function () {
    $group = ClassificationGroup::factory()
      ->withInstitution($this->institution)
      ->create(['title' => 'Secondary']);
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );

    $response = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => "Create class JSS 2 Blue in group {$group->id}"]
    )->assertOk();

    $action = latestAssistantAction($response);

    expect($action['status'])
      ->toBe('pending')
      ->and(data_get($action, 'preview.summary'))
      ->toContain('JSS 2 Blue')
      ->and($action['confirmation_token'])
      ->not->toBeEmpty()
      ->and(
        Classification::query()
          ->where('title', 'JSS 2 Blue')
          ->exists()
      )
      ->toBeFalse();

    expect(
      AssistantActionExecution::query()
        ->where('tool_name', 'create_class')
        ->count()
    )->toBe(1);
  }
);

it(
  'does not preview a class under a classification group from another institution',
  function () {
    $otherInstitution = Institution::factory()->create();
    $otherGroup = ClassificationGroup::factory()
      ->withInstitution($otherInstitution)
      ->create(['title' => 'Other institution group']);
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );

    $response = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => "Create class JSS 2 Blue in group {$otherGroup->id}"]
    )->assertOk();

    expect(latestAssistantAction($response))
      ->toBeNull()
      ->and($response->json('conversation.messages.1.content'))
      ->toContain('classification group')
      ->and(
        Classification::query()
          ->where('institution_id', $this->institution->id)
          ->where('title', 'JSS 2 Blue')
          ->exists()
      )
      ->toBeFalse();
  }
);

it(
  'cancels a pending action atomically and leaves no runnable confirmation',
  function () {
    $group = ClassificationGroup::factory()
      ->withInstitution($this->institution)
      ->create();
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );
    $prepared = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => "Create class JSS 2 Cancelled in group {$group->id}"]
    )->assertOk();
    $action = latestAssistantAction($prepared);

    $cancelled = $this->postJson(
      route('institutions.assistant.actions.cancel', [
        $this->institution,
        $conversation,
        $action['id']
      ]),
      ['confirmation_token' => $action['confirmation_token']]
    )->assertOk();

    expect(
      AssistantActionExecution::query()
        ->whereKey($action['id'])
        ->value('status')
    )
      ->toBe('cancelled')
      ->and(latestAssistantAction($cancelled)['status'])
      ->toBe('cancelled')
      ->and(
        Classification::query()
          ->where('title', 'JSS 2 Cancelled')
          ->exists()
      )
      ->toBeFalse();
  }
);

it(
  'confirms a class action through existing authorization and audit paths',
  function () {
    $group = ClassificationGroup::factory()
      ->withInstitution($this->institution)
      ->create(['title' => 'Secondary']);
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );

    $prepared = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => "Create class JSS 2 Blue in group {$group->id}"]
    );
    $action = latestAssistantAction($prepared);

    $confirmed = $this->postJson(
      route('institutions.assistant.actions.confirm', [
        $this->institution,
        $conversation,
        $action['id']
      ]),
      ['confirmation_token' => $action['confirmation_token']]
    )->assertOk();

    $class = Classification::query()
      ->where('institution_id', $this->institution->id)
      ->where('title', 'JSS 2 Blue')
      ->first();

    expect($class)
      ->not->toBeNull()
      ->and($class->classification_group_id)
      ->toBe($group->id)
      ->and(data_get(latestAssistantAction($confirmed), 'status'))
      ->toBe('executed');

    expect(
      ActivityLog::query()
        ->where('event', 'assistant.action.executed')
        ->exists()
    )->toBeTrue();
  }
);

it(
  'does not duplicate a confirmed action when the confirmation is replayed',
  function () {
    $group = ClassificationGroup::factory()
      ->withInstitution($this->institution)
      ->create();
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );
    $prepared = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => "Create class JSS 3 Red in group {$group->id}"]
    );
    $action = latestAssistantAction($prepared);
    $confirmRoute = route('institutions.assistant.actions.confirm', [
      $this->institution,
      $conversation,
      $action['id']
    ]);

    $this->postJson($confirmRoute, [
      'confirmation_token' => $action['confirmation_token']
    ])->assertOk();
    $replay = $this->postJson($confirmRoute, [
      'confirmation_token' => $action['confirmation_token']
    ])->assertOk();

    expect(
      Classification::query()
        ->where('title', 'JSS 3 Red')
        ->count()
    )
      ->toBe(1)
      ->and($replay->json('conversation.messages'))
      ->toHaveCount(3);
  }
);

it(
  'creates and updates subjects and classes only after confirmation',
  function () {
    $group = ClassificationGroup::factory()
      ->withInstitution($this->institution)
      ->create();
    $class = Classification::factory()
      ->classificationGroup($group)
      ->create(['title' => 'JSS 1 Green']);
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );

    $subjectPrepared = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => 'Create subject Mathematics with code MATH']
    );
    $subjectAction = latestAssistantAction($subjectPrepared);
    $this->postJson(
      route('institutions.assistant.actions.confirm', [
        $this->institution,
        $conversation,
        $subjectAction['id']
      ]),
      ['confirmation_token' => $subjectAction['confirmation_token']]
    )->assertOk();

    $classPrepared = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      ['message' => "Rename class {$class->id} to \"JSS 1 Gold\""]
    );
    $classAction = latestAssistantAction($classPrepared);
    $this->postJson(
      route('institutions.assistant.actions.confirm', [
        $this->institution,
        $conversation,
        $classAction['id']
      ]),
      ['confirmation_token' => $classAction['confirmation_token']]
    )->assertOk();

    expect(
      Course::query()
        ->where('institution_id', $this->institution->id)
        ->where('code', 'MATH')
        ->exists()
    )
      ->toBeTrue()
      ->and(Classification::query()->find($class->id)->title)
      ->toBe('JSS 1 Gold');
  }
);

it('denies mutating assistant actions to non-administrator users', function () {
  $teacher = User::factory()
    ->teacher($this->institution)
    ->create();
  actingAs($teacher);
  $conversation = app(AssistantConversationService::class)->create(
    app(AssistantContextResolver::class)->resolve($this->institution),
    null
  );
  $response = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    ['message' => 'Create subject Mathematics with code MATH']
  )->assertOk();

  expect($response->json('conversation.messages.1.content'))
    ->toContain('not allowed')
    ->and(
      Course::query()
        ->where('title', 'Mathematics')
        ->exists()
    )
    ->toBeFalse();
});

it(
  'prepares and confirms an explicitly targeted internal notification',
  function () {
    $class = Classification::factory()
      ->withInstitution($this->institution)
      ->create(['title' => 'JSS 1 Blue']);
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );

    $prepared = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      [
        'message' => "Send notification to class {$class->id} title: \"Parent meeting\" body: \"Meeting on Friday\""
      ]
    )->assertOk();
    $action = latestAssistantAction($prepared);

    $this->postJson(
      route('institutions.assistant.actions.confirm', [
        $this->institution,
        $conversation,
        $action['id']
      ]),
      ['confirmation_token' => $action['confirmation_token']]
    )->assertOk();

    expect(
      InternalNotification::query()
        ->where('institution_id', $this->institution->id)
        ->where('title', 'Parent meeting')
        ->exists()
    )->toBeTrue();
  }
);

it(
  'prepares a staff member and only creates the account once confirmed',
  function () {
    $conversation = app(AssistantConversationService::class)->create(
      app(AssistantContextResolver::class)->resolve($this->institution),
      null
    );

    $prepared = $this->postJson(
      route('institutions.assistant.messages.store', [
        $this->institution,
        $conversation
      ]),
      [
        'message' =>
          'Add a teacher named Ada Lovelace with email ada.lovelace@example.com phone: 08030000001'
      ]
    )->assertOk();
    $action = latestAssistantAction($prepared);

    expect($action['tool'])
      ->toBe('create_staff')
      ->and($action['status'])
      ->toBe('pending')
      ->and(data_get($action, 'preview.changes.role'))
      ->toBe('teacher')
      ->and(
        User::query()
          ->where('email', 'ada.lovelace@example.com')
          ->exists()
      )
      ->toBeFalse();

    $this->postJson(
      route('institutions.assistant.actions.confirm', [
        $this->institution,
        $conversation,
        $action['id']
      ]),
      ['confirmation_token' => $action['confirmation_token']]
    )->assertOk();

    $user = User::query()
      ->where('email', 'ada.lovelace@example.com')
      ->first();

    expect($user)
      ->not->toBeNull()
      ->and($user->first_name)
      ->toBe('Ada')
      ->and($user->last_name)
      ->toBe('Lovelace')
      ->and(
        InstitutionUser::query()
          ->where('institution_id', $this->institution->id)
          ->where('user_id', $user->id)
          ->exists()
      )
      ->toBeTrue();

    expect(
      ActivityLog::query()
        ->where('event', 'access.user_created')
        ->exists()
    )->toBeTrue();
  }
);

it('asks for the missing staff details instead of guessing them', function () {
  $conversation = app(AssistantConversationService::class)->create(
    app(AssistantContextResolver::class)->resolve($this->institution),
    null
  );

  $response = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    ['message' => 'Add a new teacher to the school']
  )->assertOk();

  expect($response->json('conversation.messages.1.content'))
    ->toContain('first name')
    ->and(latestAssistantAction($response))
    ->toBeNull()
    ->and(AssistantActionExecution::query()->count())
    ->toBe(0);
});

it('updates only the supplied fields of an existing staff member', function () {
  $teacher = User::factory()
    ->teacher($this->institution)
    ->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
  $conversation = app(AssistantConversationService::class)->create(
    app(AssistantContextResolver::class)->resolve($this->institution),
    null
  );

  $prepared = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    [
      'message' => "Update the teacher {$teacher->email} phone: 08030000002 gender: female"
    ]
  )->assertOk();
  $action = latestAssistantAction($prepared);

  expect($action['tool'])->toBe('update_staff');

  $this->postJson(
    route('institutions.assistant.actions.confirm', [
      $this->institution,
      $conversation,
      $action['id']
    ]),
    ['confirmation_token' => $action['confirmation_token']]
  )->assertOk();

  $teacher->refresh();

  expect($teacher->phone)
    ->toBe('08030000002')
    ->and($teacher->first_name)
    ->toBe('Grace')
    ->and($teacher->last_name)
    ->toBe('Hopper');
});

it('never reaches a staff member outside the actor institution', function () {
  $otherInstitution = Institution::factory()->create();
  $otherTeacher = User::factory()
    ->teacher($otherInstitution)
    ->create();
  $conversation = app(AssistantConversationService::class)->create(
    app(AssistantContextResolver::class)->resolve($this->institution),
    null
  );

  $response = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    [
      'message' => "Update the teacher {$otherTeacher->email} phone: 08030000003"
    ]
  )->assertOk();

  expect($response->json('conversation.messages.1.content'))
    ->toContain('which staff member')
    ->and(latestAssistantAction($response))
    ->toBeNull();

  $otherTeacher->refresh();
  expect($otherTeacher->phone)->not->toBe('08030000003');
});

it('denies staff actions to a non-administrator', function () {
  $teacher = User::factory()
    ->teacher($this->institution)
    ->create();
  actingAs($teacher);
  $conversation = app(AssistantConversationService::class)->create(
    app(AssistantContextResolver::class)->resolve($this->institution),
    null
  );

  $response = $this->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversation
    ]),
    [
      'message' =>
        'Add a teacher named Alan Turing with email alan.turing@example.com'
    ]
  )->assertOk();

  expect($response->json('conversation.messages.1.content'))
    ->toContain('not allowed')
    ->and(
      User::query()
        ->where('email', 'alan.turing@example.com')
        ->exists()
    )
    ->toBeFalse();
});
