<?php

namespace App\Support\UITableFilters\Concerns;

use App\Enums\DateRangeKeyword;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

trait HasDateRangeFilters
{
  protected function extraDateRangeColumns(): array
  {
    return [];
  }

  protected function dateRangeColumns(): array
  {
    return [
      'created_at' => $this->baseQuery->getModel()->qualifyColumn('created_at'),
      'updated_at' => $this->baseQuery->getModel()->qualifyColumn('updated_at'),
      ...$this->extraDateRangeColumns()
    ];
  }

  protected function applyDateRangeFilters(): static
  {
    foreach ($this->dateRangeColumns() as $filterKey => $column) {
      $filterData = $this->requestGet($filterKey);

      if (!is_array($filterData) || !$this->hasDateRangeValues($filterData)) {
        continue;
      }

      [$dateFrom, $dateTo] = $this->resolveDateRange($filterData);

      if ($dateFrom && $dateTo) {
        $this->baseQuery->whereBetween($column, [$dateFrom, $dateTo]);
        continue;
      }

      if ($dateFrom) {
        $this->baseQuery->where($column, '>=', $dateFrom);
      }

      if ($dateTo) {
        $this->baseQuery->where($column, '<=', $dateTo);
      }
    }

    return $this;
  }

  protected function dateRangeValidationRules(): array
  {
    $rules = [];

    foreach (array_keys($this->dateRangeColumns()) as $filterKey) {
      $rules[$filterKey] = ['sometimes', 'array'];
      $rules["{$filterKey}.date_from"] = ['sometimes', 'date'];
      $rules["{$filterKey}.date_to"] = ['sometimes', 'date'];
      $rules["{$filterKey}.keyword"] = [
        'sometimes',
        'string',
        Rule::enum(DateRangeKeyword::class)
      ];
    }

    return $rules;
  }

  private function hasDateRangeValues(array $filterData): bool
  {
    return !empty($filterData['keyword']) ||
      !empty($filterData['date_from']) ||
      !empty($filterData['date_to']);
  }

  private function resolveDateRange(array $filterData): array
  {
    $keyword = $filterData['keyword'] ?? null;

    if ($keyword) {
      return DateRangeKeyword::from($keyword)->resolveDateRange();
    }

    return [
      isset($filterData['date_from'])
        ? Carbon::parse($filterData['date_from'])->startOfDay()
        : null,
      isset($filterData['date_to'])
        ? Carbon::parse($filterData['date_to'])->endOfDay()
        : null
    ];
  }
}
