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
    'retentionCategory' => 'activity_logs.retention_category'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'category' => ['sometimes', new Enum(ActivityLogCategory::class)],
      'event' => ['sometimes', 'string'],
      'actor' => ['sometimes', 'string'],
      'actor_role' => ['sometimes', 'string'],
      'subject' => ['sometimes', 'string'],
      'subject_type' => ['sometimes', 'string'],
      'subject_search' => ['sometimes', 'string'],
      'severity' => ['sometimes', new Enum(ActivityLogSeverity::class)],
      'institution_id' => ['sometimes', 'integer'],
      'institution_group_id' => ['sometimes', 'integer'],
      'ip_address' => ['sometimes', 'string'],
      'request_id' => ['sometimes', 'string'],
      'impersonated_only' => ['sometimes', 'boolean'],
      'retention_category' => [
        'sometimes',
        'string',
        'in:normal,security,financial'
      ]
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q
        ->where('activity_logs.event', 'like', "%$search%")
        ->orWhere('activity_logs.category', 'like', "%$search%")
        ->orWhere('activity_logs.action', 'like', "%$search%")
        ->orWhere('activity_logs.actor_name', 'like', "%$search%")
        ->orWhere('activity_logs.actor_role', 'like', "%$search%")
        ->orWhere('activity_logs.subject_name', 'like', "%$search%")
        ->orWhere('activity_logs.subject_type', 'like', "%$search%")
        ->orWhere('activity_logs.description', 'like', "%$search%")
        ->orWhere('activity_logs.ip_address', 'like', "%$search%")
        ->orWhere('activity_logs.request_id', 'like', "%$search%")
    );
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('activity_logs.institution_id', $value)
      )
      ->when(
        $this->requestGet('institution_group_id'),
        fn($q, $value) => $q->where(
          'activity_logs.institution_group_id',
          $value
        )
      )
      ->when(
        $this->requestGet('category'),
        fn($q, $value) => $q->where('activity_logs.category', $value)
      )
      ->when(
        $this->requestGet('event'),
        fn($q, $value) => $q->where('activity_logs.event', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('actor'),
        fn($q, $value) => $q->where(
          'activity_logs.actor_name',
          'like',
          "%$value%"
        )
      )
      ->when(
        $this->requestGet('actor_role'),
        fn($q, $value) => $q->where(
          'activity_logs.actor_role',
          'like',
          "%$value%"
        )
      )
      ->when(
        $this->requestGet('subject'),
        fn($q, $value) => $q->where(
          'activity_logs.subject_name',
          'like',
          "%$value%"
        )
      )
      ->when(
        $this->requestGet('subject_type'),
        fn($q, $value) => $q->where(
          'activity_logs.subject_type',
          'like',
          "%$value%"
        )
      )
      ->when(
        $this->requestGet('subject_search'),
        fn($q, $value) => $q->where(
          fn($query) => $query
            ->where('activity_logs.subject_name', 'like', "%$value%")
            ->orWhere('activity_logs.subject_type', 'like', "%$value%")
        )
      )
      ->when(
        $this->requestGet('severity'),
        fn($q, $value) => $q->where('activity_logs.severity', $value)
      )
      ->when(
        $this->requestGet('ip_address'),
        fn($q, $value) => $q->where(
          'activity_logs.ip_address',
          'like',
          "%$value%"
        )
      )
      ->when(
        $this->requestGet('request_id'),
        fn($q, $value) => $q->where(
          'activity_logs.request_id',
          'like',
          "%$value%"
        )
      )
      ->when(
        $this->requestGet('impersonated_only'),
        fn($q) => $q->whereNotNull('activity_logs.impersonator_id')
      )
      ->when(
        $this->requestGet('retention_category'),
        fn($q, $value) => $q->where('activity_logs.retention_category', $value)
      );

    return $this;
  }
}
