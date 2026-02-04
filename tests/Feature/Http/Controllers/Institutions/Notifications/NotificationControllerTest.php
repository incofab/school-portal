<?php

use App\Actions\Notifications\CreateInternalNotification;
use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InternalNotification;
use App\Models\Student;
use App\Support\MorphMap;
use App\Support\Notifications\NotificationViewer;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->institutionUser = $this->institution->institutionUsers->first();

  $this->classificationGroup = ClassificationGroup::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classification = Classification::factory()
    ->classificationGroup($this->classificationGroup)
    ->create();

  $this->studentInstitutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create(['role' => InstitutionUserType::Student->value]);
  $this->student = Student::factory()
    ->withInstitution(
      $this->institution,
      $this->classification,
      $this->studentInstitutionUser
    )
    ->create();

  actingAs($this->instAdmin);
});

it('creates a notification with multiple target types', function () {
  postJson(
    route('institutions.notifications.store', $this->institution->uuid),
    [
      'title' => 'Class Update',
      'body' => 'A new message for your class.',
      'targets' => [
        [
          'type' => MorphMap::key(Classification::class),
          'id' => $this->classification->id
        ],
        [
          'type' => MorphMap::key(InstitutionUser::class),
          'id' => $this->institutionUser->id
        ]
      ]
    ]
  )->assertOk();

  $notification = InternalNotification::query()
    ->latest()
    ->first();

  $this->assertDatabaseHas('internal_notifications', [
    'id' => $notification->id,
    'institution_id' => $this->institution->id,
    'sender_type' => MorphMap::key(InstitutionUser::class),
    'sender_id' => $this->institutionUser->id,
    'title' => 'Class Update'
  ]);

  $this->assertDatabaseHas('internal_notification_targets', [
    'internal_notification_id' => $notification->id,
    'notifiable_type' => MorphMap::key(Classification::class),
    'notifiable_id' => $this->classification->id
  ]);

  $this->assertDatabaseHas('internal_notification_targets', [
    'internal_notification_id' => $notification->id,
    'notifiable_type' => MorphMap::key(InstitutionUser::class),
    'notifiable_id' => $this->institutionUser->id
  ]);
});

it('marks notifications as read when the list is visited', function () {
  $notification = app(CreateInternalNotification::class)->execute(
    $this->institutionUser,
    [$this->institutionUser],
    'Staff Notice',
    'Please check your dashboard.',
    null,
    null,
    [],
    $this->institution
  );

  $viewer = NotificationViewer::make($this->instAdmin, $this->institutionUser);

  expect(InternalNotification::unreadCountForViewer($viewer))->toBe(1);

  getJson(route('institutions.notifications.index', $this->institution->uuid))
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page->component(
        'institutions/notifications/list-notifications'
      )
    );

  $this->assertDatabaseHas('internal_notification_reads', [
    'internal_notification_id' => $notification->id,
    'reader_type' => MorphMap::key(InstitutionUser::class),
    'reader_id' => $this->institutionUser->id
  ]);

  expect(InternalNotification::unreadCountForViewer($viewer))->toBe(0);
});

it('counts a classification notification for a student viewer', function () {
  app(CreateInternalNotification::class)->execute(
    $this->institutionUser,
    [$this->classification],
    'Class Notice',
    'Announcement for this class.',
    null,
    null,
    [],
    $this->institution
  );

  $viewer = NotificationViewer::make(
    $this->studentInstitutionUser->user,
    $this->studentInstitutionUser
  );

  expect(InternalNotification::unreadCountForViewer($viewer))->toBe(1);
});

it('prevents students from creating notifications', function () {
  actingAs($this->studentInstitutionUser->user);

  postJson(
    route('institutions.notifications.store', $this->institution->uuid),
    [
      'title' => 'Student Notice',
      'targets' => [
        [
          'type' => MorphMap::key(Classification::class),
          'id' => $this->classification->id
        ]
      ]
    ]
  )->assertStatus(403);
});

it('shows sent notifications with read ratio and filters', function () {
  $notification = app(CreateInternalNotification::class)->execute(
    $this->institutionUser,
    [$this->classification],
    'Class Broadcast',
    'For all class members.',
    null,
    'class-update',
    [],
    $this->institution
  );

  actingAs($this->studentInstitutionUser->user);
  getJson(
    route('institutions.notifications.index', $this->institution->uuid)
  )->assertOk();

  actingAs($this->instAdmin);
  getJson(
    route('institutions.notifications.sent.index', $this->institution->uuid) .
      '?type=class-update'
  )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/notifications/list-sent-notifications')
        ->where('notifications.data.0.id', $notification->id)
        ->where('notifications.data.0.reads_count', 1)
        ->where('notifications.data.0.targets_count', 1)
    );
});

it(
  'shows sent notifications created by other staff in the institution',
  function () {
    $teacherInstitutionUser = InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->create(['role' => InstitutionUserType::Teacher->value]);

    $otherStaffNotification = app(CreateInternalNotification::class)->execute(
      $teacherInstitutionUser,
      [$this->classification],
      'Teacher Created',
      'Visible to all staff in sent list',
      null,
      'broadcast',
      [],
      $this->institution
    );

    getJson(
      route('institutions.notifications.sent.index', $this->institution->uuid)
    )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/notifications/list-sent-notifications')
          ->where('notifications.data.0.id', $otherStaffNotification->id)
          ->where('notifications.data.0.sender_id', $teacherInstitutionUser->id)
      );
  }
);

it(
  'shows sent notification recipients and blocks delete when read',
  function () {
    $notification = app(CreateInternalNotification::class)->execute(
      $this->institutionUser,
      [$this->classification],
      'Class Broadcast',
      'For all class members.',
      null,
      null,
      [],
      $this->institution
    );

    getJson(
      route('institutions.notifications.sent.show', [
        $this->institution->uuid,
        $notification->id
      ])
    )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/notifications/show-sent-notification')
          ->where('notification.reads_count', 0)
          ->where('notification.targets_count', 1)
      );

    deleteJson(
      route('institutions.notifications.sent.destroy', [
        $this->institution->uuid,
        $notification->id
      ])
    )->assertOk();

    $this->assertDatabaseMissing('internal_notifications', [
      'id' => $notification->id
    ]);

    $readNotification = app(CreateInternalNotification::class)->execute(
      $this->institutionUser,
      [$this->classification],
      'Read Guard',
      'Cannot delete this after read.',
      null,
      null,
      [],
      $this->institution
    );

    actingAs($this->studentInstitutionUser->user);
    getJson(
      route('institutions.notifications.index', $this->institution->uuid)
    )->assertOk();

    actingAs($this->instAdmin);
    deleteJson(
      route('institutions.notifications.sent.destroy', [
        $this->institution->uuid,
        $readNotification->id
      ])
    )->assertStatus(422);

    $this->assertDatabaseHas('internal_notifications', [
      'id' => $readNotification->id
    ]);
  }
);
