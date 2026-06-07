<?php

namespace App\Support\UITableFilters;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use Illuminate\Validation\Rules\Enum;

class ActivityLogUITableFilters extends BaseUITableFilter
{
    protected array $sortableColumns = [
        'createdAt' => 'activity_logs.created_at',
        'category' => 'activity_logs.category',
        'event' => 'activity_logs.event',
        'action' => 'activity_logs.action',
        'severity' => 'activity_logs.severity',
    ];

    protected function extraValidationRules(): array
    {
        return [
            'category' => ['sometimes', new Enum(ActivityLogCategory::class)],
            'event' => ['sometimes', 'string'],
            'actor' => ['sometimes', 'string'],
            'subject' => ['sometimes', 'string'],
            'severity' => ['sometimes', new Enum(ActivityLogSeverity::class)],
            'institution_id' => ['sometimes', 'integer'],
        ];
    }

    protected function generalSearch(string $search)
    {
        $this->baseQuery->where(
            fn ($q) => $q
                ->where('activity_logs.event', 'like', "%$search%")
                ->orWhere('activity_logs.category', 'like', "%$search%")
                ->orWhere('activity_logs.action', 'like', "%$search%")
                ->orWhere('activity_logs.actor_name', 'like', "%$search%")
                ->orWhere('activity_logs.subject_name', 'like', "%$search%")
                ->orWhere('activity_logs.description', 'like', "%$search%")
        );
    }

    protected function directQuery()
    {
        $this->baseQuery
            ->when(
                $this->requestGet('institution_id'),
                fn ($q, $value) => $q->where('activity_logs.institution_id', $value)
            )
            ->when(
                $this->requestGet('category'),
                fn ($q, $value) => $q->where('activity_logs.category', $value)
            )
            ->when(
                $this->requestGet('event'),
                fn ($q, $value) => $q->where('activity_logs.event', 'like', "%$value%")
            )
            ->when(
                $this->requestGet('actor'),
                fn ($q, $value) => $q->where('activity_logs.actor_name', 'like', "%$value%")
            )
            ->when(
                $this->requestGet('subject'),
                fn ($q, $value) => $q->where('activity_logs.subject_name', 'like', "%$value%")
            )
            ->when(
                $this->requestGet('severity'),
                fn ($q, $value) => $q->where('activity_logs.severity', $value)
            );

        return $this;
    }
}
