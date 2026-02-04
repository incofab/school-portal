<?php

namespace App\Http\Controllers\Institutions\Notifications;

use App\Actions\Notifications\CreateInternalNotification;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInternalNotificationRequest;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InternalNotification;
use App\Models\Partner;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use App\Support\Notifications\NotificationRecipientsResolver;
use App\Support\Notifications\NotificationViewer;
use App\Support\UITableFilters\InternalNotificationsUITableFilters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher,
      InstitutionUserType::Accountant
    ])->except(['index']);
  }

  public function index(Institution $institution)
  {
    $viewer = NotificationViewer::fromRequest();
    abort_unless($viewer, 403);

    InternalNotification::markAllAsRead($viewer);

    $notifications = InternalNotification::query()
      ->forViewer($viewer)
      ->with([
        'sender' => function (MorphTo $morphTo) {
          $morphTo->morphWith([
            InstitutionUser::class => ['user'],
            Partner::class => ['user'],
            User::class => []
          ]);
        }
      ])
      ->latest('internal_notifications.id');

    return inertia('institutions/notifications/list-notifications', [
      'notifications' => paginateFromRequest($notifications)
    ]);
  }

  public function sentIndex(Institution $institution, Request $request)
  {
    $notifications = InternalNotification::query()->select(
      'internal_notifications.*'
    );

    InternalNotificationsUITableFilters::make($request->all(), $notifications)
      ->forInstitution()
      ->filterQuery()
      ->getQuery()
      ->with([
        'sender' => function (MorphTo $morphTo) {
          $morphTo->morphWith([
            InstitutionUser::class => ['user'],
            Partner::class => ['user'],
            User::class => []
          ]);
        }
      ])
      ->withCount('reads', 'targets')
      ->latest('internal_notifications.id');

    return inertia('institutions/notifications/list-sent-notifications', [
      'notifications' => paginateFromRequest($notifications)
    ]);
  }

  public function create(Institution $institution)
  {
    return inertia('institutions/notifications/create-notification');
  }

  public function sentShow(
    Institution $institution,
    InternalNotification $internalNotification
  ) {
    $internalNotification
      ->load([
        'sender' => function (MorphTo $morphTo) {
          $morphTo->morphWith([
            InstitutionUser::class => ['user'],
            User::class => []
          ]);
        },
        'targets'
      ])
      ->loadCount('reads', 'targets');
    return inertia('institutions/notifications/show-sent-notification', [
      'notification' => $internalNotification,
      'recipients' => paginateFromRequest($internalNotification->reads())
    ]);
  }

  public function store(
    Institution $institution,
    StoreInternalNotificationRequest $request
  ) {
    $sender = currentInstitutionUser() ?? currentUser();
    abort_unless($sender, 403);

    $targets = $this->resolveTargets($request->validated('targets'));
    $notification = app(CreateInternalNotification::class)->execute(
      $sender,
      $targets,
      $request->validated('title'),
      $request->validated('body'),
      $request->validated('action_url'),
      $request->validated('type'),
      $request->validated('data', []),
      currentInstitution()
    );

    return $this->apiRes(
      successRes('Notification created', ['notification' => $notification])
    );
  }

  public function sentDestroy(
    Institution $institution,
    InternalNotification $internalNotification
  ) {
    $institutionUser = currentInstitutionUser();
    abort_if(
      $internalNotification->institution_id !== $institution->id ||
        !$institutionUser->isAdmin(),
      403,
      'You are not authorized to delete this notification.'
    );

    abort_if(
      $internalNotification->reads()->exists(),
      422,
      'Cannot delete. Notification has already been read.'
    );

    $internalNotification->delete();

    return $this->apiRes(successRes('Notification deleted'));
  }

  private function resolveTargets(array $targets): array
  {
    $allowedTargetTypes = array_filter([
      MorphMap::key(User::class),
      MorphMap::key(InstitutionUser::class),
      MorphMap::key(Student::class),
      MorphMap::key(Classification::class),
      MorphMap::key(ClassificationGroup::class)
    ]);

    $resolved = [];

    foreach ($targets as $target) {
      $type = $target['type'] ?? null;
      $id = $target['id'] ?? null;

      if (!$type || !$id) {
        throw ValidationException::withMessages([
          'targets' => 'Each target must include a type and id.'
        ]);
      }

      if (!in_array($type, $allowedTargetTypes, true)) {
        throw ValidationException::withMessages([
          'targets' => 'Unsupported target type provided.'
        ]);
      }

      $modelClass = Relation::getMorphedModel($type);
      /** @var Model|null $model */
      $model = $modelClass ? (new $modelClass())->find($id) : null;

      if (!$model) {
        throw ValidationException::withMessages([
          'targets' => 'One or more targets could not be resolved.'
        ]);
      }

      $resolved[] = $model;
    }

    return $resolved;
  }

  /** @deprecated */
  private function getSentNotification(
    Institution $institution,
    int $internalNotification
  ): InternalNotification {
    return InternalNotification::query()
      ->where('institution_id', $institution->id)
      ->whereKey($internalNotification)
      ->with([
        'sender' => function (MorphTo $morphTo) {
          $morphTo->morphWith([
            InstitutionUser::class => ['user'],
            Partner::class => ['user'],
            User::class => []
          ]);
        },
        'targets',
        'reads'
      ])
      ->firstOrFail();
  }
}
