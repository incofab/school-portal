<?php

namespace App\Support\Audit;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use App\Models\ActivityLog;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * A fluent builder for creating ActivityLog entries.
 * Handles context capturing and data sanitization before persistence.
 */
class ActivityLogger
{
    private array $payload = [];

    private bool $useDefaultContext = true;

    public function event(string $event): static
    {
        $this->payload['event'] = $event;

        return $this;
    }

    public function category(ActivityLogCategory|string $category): static
    {
        $this->payload['category'] = $category instanceof ActivityLogCategory
          ? $category->value
          : $category;

        return $this;
    }

    public function action(string $action): static
    {
        $this->payload['action'] = $action;

        return $this;
    }

    public function by(?Model $actor, ?string $guard = null): static
    {
        if (! $actor) {
            return $this;
        }

        $this->payload['actor_type'] = $actor::class;
        $this->payload['actor_id'] = $actor->getKey();
        $this->payload['actor_name'] = $this->displayName($actor);
        $this->payload['actor_role'] = $this->actorRole($actor);
        $this->payload['actor_guard'] = $guard ?? Auth::getDefaultDriver();

        return $this;
    }

    public function on(?Model $subject): static
    {
        if (! $subject) {
            return $this;
        }

        $this->payload['subject_type'] = $subject::class;
        $this->payload['subject_id'] = $subject->getKey();
        $this->payload['subject_name'] = $this->displayName($subject);

        return $this;
    }

    public function inInstitution(?Institution $institution): static
    {
        if (! $institution) {
            return $this;
        }

        $this->payload['institution_id'] = $institution->id;
        $this->payload['institution_group_id'] = $institution->institution_group_id;

        return $this;
    }

    public function inInstitutionGroup(?InstitutionGroup $institutionGroup): static
    {
        if (! $institutionGroup) {
            return $this;
        }

        $this->payload['institution_group_id'] = $institutionGroup->id;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->payload['description'] = $description;

        return $this;
    }

    public function properties(array $properties): static
    {
        $this->payload['properties'] = ActivityLogSanitizer::sanitizeJsonArray($properties);

        return $this;
    }

    public function oldValues(array $oldValues): static
    {
        $this->payload['old_values'] = ActivityLogSanitizer::sanitizeJsonArray($oldValues);

        return $this;
    }

    public function newValues(array $newValues): static
    {
        $this->payload['new_values'] = ActivityLogSanitizer::sanitizeJsonArray($newValues);

        return $this;
    }

    public function severity(ActivityLogSeverity|string $severity): static
    {
        $this->payload['severity'] = $severity instanceof ActivityLogSeverity
          ? $severity->value
          : $severity;

        return $this;
    }

    public function withoutDefaultContext(): static
    {
        $this->useDefaultContext = false;

        return $this;
    }

    /**
     * Finalize the payload and persist the activity log entry.
     */
    public function log(): ActivityLog
    {
        if ($this->useDefaultContext && empty($this->payload['actor_id'])) {
            $this->by(Auth::user());
        }

        if ($this->useDefaultContext && empty($this->payload['institution_id'])) {
            $this->inInstitution(currentInstitution());
        }

        $payload = [
            ...AuditRequestContext::capture(),
            ...$this->payload,
            'action' => $this->payload['action'] ?? 'performed',
            'category' => $this->payload['category'] ?? ActivityLogCategory::System->value,
            'event' => $this->payload['event'] ?? 'activity.logged',
            'severity' => $this->payload['severity'] ?? ActivityLogSeverity::Info->value,
        ];

        return ActivityLog::query()->create($payload);
    }

    private function displayName(Model $model): ?string
    {
        foreach (['full_name', 'name', 'title', 'reference', 'email'] as $attribute) {
            if (filled($model->getAttribute($attribute))) {
                return (string) $model->getAttribute($attribute);
            }
        }

        if (method_exists($model, 'getReference')) {
            return (string) $model->getReference();
        }

        return class_basename($model).' #'.$model->getKey();
    }

    private function actorRole(Model $actor): ?string
    {
        if ($actor instanceof User) {
            if ($actor->isManager()) {
                return $actor
                    ->roles()
                    ->pluck('name')
                    ->implode(', ');
            }

            $role = $actor->institutionUser()?->first()?->role;

            return $role instanceof \BackedEnum ? $role->value : $role;
        }

        return null;
    }
}
