<?php

use App\Models\ActivityLog;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

beforeEach(function () {
  ActivityLog::query()->delete();
});

it('filters activity logs by every advanced filter', function () {
  $institution = Institution::factory()->create();
  $otherInstitution = Institution::factory()->create();
  $actor = User::factory()->create([
    'first_name' => 'Ada',
    'last_name' => 'Auditor'
  ]);

  ActivityLog::factory()
    ->forInstitution($institution)
    ->create([
      'actor_type' => User::class,
      'actor_id' => $actor->id,
      'actor_name' => 'Ada Auditor',
      'actor_role' => 'admin',
      'category' => 'security',
      'event' => 'security.impersonation_started',
      'subject_type' => Institution::class,
      'subject_name' => 'Target Institution',
      'severity' => 'security',
      'retention_category' => 'security',
      'ip_address' => '10.0.0.5',
      'request_id' => 'req-filter-match',
      'impersonator_type' => User::class,
      'impersonator_id' => $actor->id,
      'created_at' => now()->subDay()
    ]);

  ActivityLog::factory()
    ->forInstitution($otherInstitution)
    ->create([
      'actor_name' => 'Other Actor',
      'actor_role' => 'teacher',
      'category' => 'system',
      'event' => 'system.other',
      'subject_type' => User::class,
      'subject_name' => 'Other Subject',
      'severity' => 'info',
      'retention_category' => 'normal',
      'ip_address' => '10.0.0.9',
      'request_id' => 'req-filter-other',
      'created_at' => now()->subDays(10)
    ]);

  actingAs(
    User::factory()
      ->adminManager()
      ->create()
  )
    ->getJson(
      route('managers.activity-logs.index', [
        'created_at' => [
          'date_from' => now()
            ->subDays(2)
            ->toDateString(),
          'date_to' => now()->toDateString()
        ],
        'institution_id' => $institution->id,
        'institution_group_id' => $institution->institution_group_id,
        'actor' => 'Ada',
        'actor_role' => 'admin',
        'category' => 'security',
        'event' => 'impersonation',
        'subject_type' => 'Institution',
        'subject_search' => 'Target',
        'severity' => 'security',
        'ip_address' => '10.0.0.5',
        'request_id' => 'req-filter-match',
        'impersonated_only' => '1',
        'retention_category' => 'security'
      ])
    )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->has('activityLogs.data', 1)
        ->where('activityLogs.data.0.event', 'security.impersonation_started')
    );
});

it('exports authorized manager audit logs with active filters', function () {
  $institution = Institution::factory()->create();
  $otherInstitution = Institution::factory()->create();

  ActivityLog::factory()
    ->forInstitution($institution)
    ->create(['event' => 'audit.export_visible', 'category' => 'security']);
  ActivityLog::factory()
    ->forInstitution($otherInstitution)
    ->create(['event' => 'audit.export_hidden', 'category' => 'system']);

  $response = actingAs(
    User::factory()
      ->adminManager()
      ->create()
  )
    ->get(
      route('managers.activity-logs.export', [
        'institution_id' => $institution->id
      ])
    )
    ->assertOk();

  expect($response->streamedContent())
    ->toContain('audit.export_visible')
    ->not->toContain('audit.export_hidden');
});

it(
  'prevents institution exports from including other institutions',
  function () {
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();

    ActivityLog::factory()
      ->forInstitution($institution)
      ->create(['event' => 'audit.institution_visible']);
    ActivityLog::factory()
      ->forInstitution($otherInstitution)
      ->create(['event' => 'audit.institution_hidden']);

    $response = actingAs($institution->createdBy)
      ->get(
        route('institutions.activity-logs.export', [
          'institution' => $institution->uuid,
          'institution_id' => $otherInstitution->id
        ])
      )
      ->assertOk();

    expect($response->streamedContent())
      ->toContain('audit.institution_visible')
      ->not->toContain('audit.institution_hidden');
  }
);

it('prunes only logs eligible for their retention category', function () {
  config()->set('audit.retention_days', [
    'normal' => 30,
    'security' => 90,
    'financial' => 365
  ]);

  ActivityLog::factory()->create([
    'event' => 'audit.old_normal',
    'retention_category' => 'normal',
    'created_at' => now()->subDays(31)
  ]);
  ActivityLog::factory()->create([
    'event' => 'audit.recent_normal',
    'retention_category' => 'normal',
    'created_at' => now()->subDays(29)
  ]);
  ActivityLog::factory()->create([
    'event' => 'audit.security_kept',
    'retention_category' => 'security',
    'created_at' => now()->subDays(31)
  ]);

  artisan('audit:prune')->assertSuccessful();

  expect(
    ActivityLog::query()
      ->where('event', 'audit.old_normal')
      ->exists()
  )
    ->toBeFalse()
    ->and(
      ActivityLog::query()
        ->where('event', 'audit.recent_normal')
        ->exists()
    )
    ->toBeTrue()
    ->and(
      ActivityLog::query()
        ->where('event', 'audit.security_kept')
        ->exists()
    )
    ->toBeTrue();
});

it(
  'keeps security and financial logs longer than normal logs when configured',
  function () {
    config()->set('audit.retention_days', [
      'normal' => 30,
      'security' => 180,
      'financial' => 365
    ]);

    ActivityLog::factory()->create([
      'event' => 'audit.normal_pruned',
      'retention_category' => 'normal',
      'created_at' => now()->subDays(40)
    ]);
    ActivityLog::factory()->create([
      'event' => 'audit.security_retained',
      'retention_category' => 'security',
      'created_at' => now()->subDays(40)
    ]);
    ActivityLog::factory()->create([
      'event' => 'audit.financial_retained',
      'retention_category' => 'financial',
      'created_at' => now()->subDays(40)
    ]);

    artisan('audit:prune')->assertSuccessful();

    expect(
      ActivityLog::query()
        ->where('event', 'audit.normal_pruned')
        ->exists()
    )
      ->toBeFalse()
      ->and(
        ActivityLog::query()
          ->where('event', 'audit.security_retained')
          ->exists()
      )
      ->toBeTrue()
      ->and(
        ActivityLog::query()
          ->where('event', 'audit.financial_retained')
          ->exists()
      )
      ->toBeTrue();
  }
);

it(
  'verifies unchanged audit log hashes and fails after tampering',
  function () {
    $first = ActivityLog::query()->create([
      'action' => 'created',
      'category' => 'system',
      'event' => 'audit.integrity_first'
    ]);
    ActivityLog::query()->create([
      'action' => 'created',
      'category' => 'system',
      'event' => 'audit.integrity_second'
    ]);

    expect(ActivityLog::verifyChain()['ok'])
      ->toBeTrue()
      ->and($first->fresh()->verifyIntegrity())
      ->toBeTrue();

    DB::table('activity_logs')
      ->where('id', $first->id)
      ->update(['description' => 'Tampered description']);

    expect(ActivityLog::verifyChain()['ok'])
      ->toBeFalse()
      ->and($first->fresh()->verifyIntegrity())
      ->toBeFalse();
  }
);

it(
  'keeps activity logs append only through normal model operations',
  function () {
    $log = ActivityLog::factory()->create(['event' => 'audit.append_only']);
    $log->description = 'Changed';

    expect($log->save())
      ->toBeFalse()
      ->and($log->delete())
      ->toBeFalse()
      ->and(
        ActivityLog::query()
          ->whereKey($log->id)
          ->exists()
      )
      ->toBeTrue();
  }
);
