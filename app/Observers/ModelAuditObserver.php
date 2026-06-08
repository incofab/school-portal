<?php

namespace App\Observers;

use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Support\Audit\ActivityLogger;
use App\Support\Audit\ModelAuditRegistry;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ModelAuditObserver
{
  private static array $pendingUpdates = [];

  public function created(Model $model): void
  {
    $this->log(
      $model,
      'created',
      newValues: ModelAuditRegistry::filterValues($model->getAttributes())
    );
  }

  public function updating(Model $model): void
  {
    $changes = ModelAuditRegistry::filterValues($model->getDirty());

    if ($changes === []) {
      return;
    }

    $oldValues = ModelAuditRegistry::filterValues(
      collect(array_keys($changes))
        ->mapWithKeys(fn(string $key) => [$key => $model->getOriginal($key)])
        ->all()
    );

    self::$pendingUpdates[spl_object_id($model)] = [
      'old' => $oldValues,
      'new' => $changes
    ];
  }

  public function updated(Model $model): void
  {
    $key = spl_object_id($model);
    $values = self::$pendingUpdates[$key] ?? null;
    unset(self::$pendingUpdates[$key]);

    if (!$values || $values['new'] === []) {
      return;
    }

    $this->log(
      $model,
      'updated',
      oldValues: $values['old'],
      newValues: $values['new']
    );
  }

  public function deleted(Model $model): void
  {
    if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
      return;
    }

    $this->log(
      $model,
      'deleted',
      oldValues: ModelAuditRegistry::filterValues($model->getAttributes())
    );
  }

  public function restored(Model $model): void
  {
    $this->log(
      $model,
      'restored',
      newValues: ModelAuditRegistry::filterValues($model->getAttributes())
    );
  }

  public function forceDeleted(Model $model): void
  {
    $this->log(
      $model,
      'force_deleted',
      oldValues: ModelAuditRegistry::filterValues($model->getAttributes())
    );
  }

  private function log(
    Model $model,
    string $action,
    array $oldValues = [],
    array $newValues = []
  ): void {
    if (!ModelAuditRegistry::shouldAudit($model)) {
      return;
    }

    $logger = app(ActivityLogger::class)
      ->withoutDefaultContext()
      ->event(sprintf('model.%s.%s', $this->morphKey($model), $action))
      ->category(ModelAuditRegistry::category($model))
      ->action($action)
      ->by(currentUser())
      ->on($model)
      ->description(
        sprintf('%s %s', class_basename($model), str_replace('_', ' ', $action))
      )
      ->properties([
        'model' => $model::class,
        'model_key' => $model->getKey()
      ]);

    if ($oldValues !== []) {
      $logger->oldValues($oldValues);
    }

    if ($newValues !== []) {
      $logger->newValues($newValues);
    }

    [$institution, $institutionGroup] = $this->resolveContext($model);
    $logger
      ->inInstitution($institution)
      ->inInstitutionGroup($institutionGroup)
      ->log();
  }

  private function morphKey(Model $model): string
  {
    return MorphMap::key($model::class) ??
      str($model::class)
        ->classBasename()
        ->kebab()
        ->toString();
  }

  private function resolveContext(Model $model): array
  {
    $institution = $this->resolveInstitution($model);
    $institutionGroup =
      $institution?->institutionGroup ?? $this->resolveInstitutionGroup($model);

    return [$institution, $institutionGroup];
  }

  private function resolveInstitution(Model $model): ?Institution
  {
    if ($model instanceof Institution) {
      return $model;
    }

    if ($currentInstitution = currentInstitution()) {
      return $currentInstitution;
    }

    if ($model->getAttribute('institution_id')) {
      return Institution::query()->find($model->getAttribute('institution_id'));
    }

    foreach (
      [
        'institution',
        'institutionUser',
        'classification',
        'student',
        'course',
        'fee',
        'admissionApplication'
      ]
      as $relation
    ) {
      if (!method_exists($model, $relation)) {
        continue;
      }

      $related = $this->relatedModel($model, $relation);

      if ($related instanceof Institution) {
        return $related;
      }

      if ($related instanceof Model) {
        $institution = $this->resolveInstitution($related);

        if ($institution) {
          return $institution;
        }
      }
    }

    return null;
  }

  private function resolveInstitutionGroup(Model $model): ?InstitutionGroup
  {
    if ($model instanceof InstitutionGroup) {
      return $model;
    }

    if ($model->getAttribute('institution_group_id')) {
      return InstitutionGroup::query()->find(
        $model->getAttribute('institution_group_id')
      );
    }

    foreach (
      ['institutionGroup', 'withdrawable', 'accountable', 'entity']
      as $relation
    ) {
      if (!method_exists($model, $relation)) {
        continue;
      }

      $related = $this->relatedModel($model, $relation);

      if ($related instanceof InstitutionGroup) {
        return $related;
      }

      if ($related instanceof Model) {
        $institutionGroup = $this->resolveInstitutionGroup($related);

        if ($institutionGroup) {
          return $institutionGroup;
        }
      }
    }

    return null;
  }

  private function relatedModel(Model $model, string $relation): ?Model
  {
    if ($model->relationLoaded($relation)) {
      $related = $model->getRelation($relation);

      if ($related instanceof Collection) {
        return $related->first() instanceof Model ? $related->first() : null;
      }

      return $related instanceof Model ? $related : null;
    }

    $relationship = $model->{$relation}();

    if (!$relationship instanceof Relation) {
      return null;
    }

    $related = $relationship->first();

    return $related instanceof Model ? $related : null;
  }
}
