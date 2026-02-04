<?php

namespace App\Http\Controllers\Managers\Notifications;

use App\Actions\Notifications\CreateInternalNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInternalNotificationRequest;
use App\Models\Classification;
use App\Models\ClassificationGroup;
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
  public function index()
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

    return inertia('managers/notifications/list-notifications', [
      'notifications' => paginateFromRequest($notifications)
    ]);
  }

  public function sentIndex(Request $request)
  {
    $user = currentUser();

    $notifications = InternalNotification::query()
      ->select('internal_notifications.*')
      ->whereNull('institution_id');

    InternalNotificationsUITableFilters::make(
      [
        ...$request->all(),
        'senderType' => $user->getMorphClass(),
        'senderId' => $user->id
      ],
      $notifications
    )
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

    return inertia('managers/notifications/list-sent-notifications', [
      'notifications' => paginateFromRequest($notifications)
    ]);
  }

  public function create()
  {
    return inertia('managers/notifications/create-notification');
  }

  public function sentShow(InternalNotification $internalNotification)
  {
    $internalNotification
      ->load([
        'sender' => function (MorphTo $morphTo) {
          $morphTo->morphWith([
            Partner::class => ['user'],
            User::class => []
          ]);
        },
        'targets'
        // 'reads'
      ])
      ->loadCount('reads', 'targets');

    return inertia('managers/notifications/show-sent-notification', [
      'notification' => $internalNotification,
      'recipients' => paginateFromRequest($internalNotification->reads())
      // 'summary' => $summary
    ]);
  }

  public function store(StoreInternalNotificationRequest $request)
  {
    $sender = currentUser();
    abort_unless($sender, 403);

    $targets = $this->resolveTargets($request->validated('targets'));
    $notification = app(CreateInternalNotification::class)->execute(
      $sender,
      $targets,
      $request->validated('title'),
      $request->validated('body'),
      $request->validated('action_url'),
      $request->validated('type'),
      $request->validated('data', [])
    );

    return $this->apiRes(
      successRes('Notification created', ['notification' => $notification])
    );
  }

  public function sentDestroy(InternalNotification $internalNotification)
  {
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
}
