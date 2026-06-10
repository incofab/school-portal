<?php

use App\Models\ActivityLog;
use App\Models\Institution;
use App\Models\User;
use App\Support\Audit\ActivityLogger;
use App\Support\Audit\ActivityLogSanitizer;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

it('creates an activity log with the service', function () {
  $institution = Institution::factory()->create();
  $user = $institution->createdBy;

  $log = app(ActivityLogger::class)
    ->event('audit.test_event')
    ->category('system')
    ->action('created')
    ->by($user)
    ->on($institution)
    ->inInstitution($institution)
    ->description('Created a test audit event')
    ->properties(['key' => 'value'])
    ->severity('info')
    ->log();

  expect($log)
    ->toBeInstanceOf(ActivityLog::class)
    ->and($log->institution_id)
    ->toBe($institution->id)
    ->and($log->actor_id)
    ->toBe($user->id)
    ->and($log->actor_name)
    ->toBe($user->full_name)
    ->and($log->subject_id)
    ->toBe($institution->id)
    ->and($log->subject_name)
    ->toBe($institution->name)
    ->and($log->properties->toArray())
    ->toBe(['key' => 'value']);
});

it('logs guest and system activity without an actor', function () {
  $log = app(ActivityLogger::class)
    ->event('audit.system_event')
    ->category('system')
    ->action('processed')
    ->description('Processed a system event')
    ->log();

  expect($log->actor_type)
    ->toBeNull()
    ->and($log->actor_id)
    ->toBeNull()
    ->and($log->actor_name)
    ->toBeNull()
    ->and($log->event)
    ->toBe('audit.system_event');
});

it('captures request context during an http request', function () {
  Route::get('/audit-context-test', function () {
    app(ActivityLogger::class)
      ->event('audit.context_test')
      ->category('system')
      ->action('viewed')
      ->log();

    return response()->json(['ok' => true]);
  })->name('audit.context-test');

  $user = User::factory()->create();

  actingAs($user)
    ->withHeader('X-Request-ID', 'request-123')
    ->getJson('/audit-context-test')
    ->assertOk();

  $log = ActivityLog::query()
    ->where('event', 'audit.context_test')
    ->firstOrFail();

  expect($log->actor_id)
    ->toBe($user->id)
    ->and($log->route_name)
    ->toBe('audit.context-test')
    ->and($log->method)
    ->toBe('GET')
    ->and($log->request_id)
    ->toBe('request-123')
    ->and($log->url)
    ->toContain('/audit-context-test');
});

it('redacts sensitive keys in payloads and diffs', function () {
  $log = app(ActivityLogger::class)
    ->event('audit.redaction_test')
    ->category('system')
    ->action('updated')
    ->properties([
      'email' => 'visible@example.test',
      'password' => 'secret',
      'profile' => ['api_key' => 'key-value']
    ])
    ->oldValues(['token' => 'old-token'])
    ->newValues(['remember_token' => 'new-token'])
    ->log();

  expect($log->properties->toArray())
    ->toBe([
      'email' => 'visible@example.test',
      'password' => ActivityLogSanitizer::REDACTED,
      'profile' => ['api_key' => ActivityLogSanitizer::REDACTED]
    ])
    ->and($log->old_values->toArray())
    ->toBe([
      'token' => ActivityLogSanitizer::REDACTED
    ])
    ->and($log->new_values->toArray())
    ->toBe([
      'remember_token' => ActivityLogSanitizer::REDACTED
    ]);
});

it('normalizes unencodable values before storing json payloads', function () {
  $stream = fopen('php://temp', 'r');

  $log = app(ActivityLogger::class)
    ->event('audit.json_safe_test')
    ->category('system')
    ->action('created')
    ->properties([
      'resource' => $stream,
      'object' => new stdClass(),
      'nan' => NAN
    ])
    ->newValues([
      'invalid_utf8' => "bad\xB1value"
    ])
    ->log();

  fclose($stream);

  expect($log->properties->toArray())
    ->toMatchArray([
      'resource' => '[resource]',
      'object' => '[object stdClass]',
      'nan' => 'NAN'
    ])
    ->and($log->new_values['invalid_utf8'])
    ->toContain('bad');
});

it('allows admin managers to view global audit logs', function () {
  $adminManager = User::factory()
    ->adminManager()
    ->create();
  ActivityLog::query()->delete();

  ActivityLog::query()->create([
    'action' => 'created',
    'category' => 'system',
    'event' => 'audit.manager_visible',
    'severity' => 'info',
    'properties' => []
  ]);

  actingAs($adminManager)
    ->getJson(route('managers.activity-logs.index'))
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('managers/activity-logs/list-activity-logs')
        ->has('activityLogs.data', 1)
        ->where('activityLogs.data.0.event', 'audit.manager_visible')
    );
});

it('prevents partner managers from viewing global audit logs', function () {
  $partnerManager = User::factory()
    ->partnerManager()
    ->create();

  actingAs($partnerManager)
    ->getJson(route('managers.activity-logs.index'))
    ->assertForbidden();
});

it(
  'allows institution admins to view only their institution logs',
  function () {
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();
    ActivityLog::query()->delete();

    ActivityLog::factory()
      ->forInstitution($institution)
      ->create(['event' => 'audit.own_log']);
    ActivityLog::factory()
      ->forInstitution($otherInstitution)
      ->create(['event' => 'audit.other_log']);

    actingAs($institution->createdBy)
      ->getJson(route('institutions.activity-logs.index', $institution->uuid))
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/activity-logs/list-activity-logs')
          ->has('activityLogs.data', 1)
          ->where('activityLogs.data.0.event', 'audit.own_log')
      );
  }
);

it('blocks cross-institution audit log access', function () {
  $institution = Institution::factory()->create();
  $otherInstitution = Institution::factory()->create();

  actingAs($institution->createdBy)
    ->getJson(
      route('institutions.activity-logs.index', $otherInstitution->uuid)
    )
    ->assertForbidden();
});

it(
  'does not allow legacy activity log permissions to grant institution access',
  function () {
    $institution = Institution::factory()->create();
    $teacher = User::factory()
      ->teacher($institution)
      ->create();

    Permission::findOrCreate('activity-logs.view-institution');
    $teacher->givePermissionTo('activity-logs.view-institution');

    actingAs($teacher)
      ->getJson(route('institutions.activity-logs.index', $institution->uuid))
      ->assertForbidden();
  }
);

it(
  'prevents managers from accessing institution audit logs without impersonating',
  function () {
    $institution = Institution::factory()->create();
    $adminManager = User::factory()
      ->adminManager()
      ->create();

    Permission::findOrCreate('activity-logs.view-institution');
    $adminManager->givePermissionTo('activity-logs.view-institution');

    actingAs($adminManager)
      ->getJson(route('institutions.activity-logs.index', $institution->uuid))
      ->assertForbidden();
  }
);
