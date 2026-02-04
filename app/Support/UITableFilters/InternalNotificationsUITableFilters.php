<?php

namespace App\Support\UITableFilters;

class InternalNotificationsUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'internal_notifications.created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'type' => ['sometimes', 'string', 'max:100'],
      'fromDate' => ['sometimes', 'date'],
      'toDate' => ['sometimes', 'date'],
      'senderType' => ['sometimes', 'string'],
      'senderId' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(function ($query) use ($search) {
      $query
        ->where('internal_notifications.title', 'like', "%{$search}%")
        ->orWhere('internal_notifications.body', 'like', "%{$search}%");
    });

    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('internal_notifications.institution_id', $value)
      )
      ->when(
        $this->requestGet('type'),
        fn($q, $value) => $q->where('internal_notifications.type', $value)
      )
      ->when(
        $this->requestGet('fromDate'),
        fn($q, $value) => $q->whereDate('internal_notifications.created_at', '>=', $value)
      )
      ->when(
        $this->requestGet('toDate'),
        fn($q, $value) => $q->whereDate('internal_notifications.created_at', '<=', $value)
      )
      ->when(
        $this->requestGet('senderType'),
        fn($q, $value) => $q->where('internal_notifications.sender_type', $value)
      )
      ->when(
        $this->requestGet('senderId'),
        fn($q, $value) => $q->where('internal_notifications.sender_id', $value)
      );

    return $this;
  }
}
