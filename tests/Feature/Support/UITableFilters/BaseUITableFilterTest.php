<?php

use App\Models\User;
use App\Support\UITableFilters\BaseUITableFilter;
use Carbon\Carbon;

function makeBaseTableFilter(array $requestData, array $extraDateColumns = []): BaseUITableFilter
{
  return new class($requestData, User::query(), $extraDateColumns) extends BaseUITableFilter
  {
    public function __construct(
      array $requestData,
      \Illuminate\Database\Eloquent\Builder $baseQuery,
      private array $extraDateColumns = []
    ) {
      parent::__construct($requestData, $baseQuery);
    }

    protected function extraDateRangeColumns(): array
    {
      return $this->extraDateColumns;
    }

    protected function directQuery()
    {
      return $this;
    }

    protected function generalSearch(string $search)
    {
      return $this;
    }
  };
}

function normalizeBindings(array $bindings): array
{
  return array_map(
    fn($binding) => $binding instanceof Carbon
      ? $binding->toDateTimeString()
      : $binding,
    $bindings
  );
}

it('applies the default created_at date range filter', function () {
  $query = makeBaseTableFilter([
    'created_at' => [
      'date_from' => '2026-03-01',
      'date_to' => '2026-03-31'
    ]
  ])
    ->filterQuery()
    ->getQuery();

  expect($query->toSql())->toContain('`users`.`created_at` between ? and ?');
  expect(normalizeBindings($query->getBindings()))->toBe([
    '2026-03-01 00:00:00',
    '2026-03-31 23:59:59'
  ]);
});

it('applies keyword ranges to the default updated_at filter', function () {
  Carbon::setTestNow('2026-03-26 12:00:00');

  $query = makeBaseTableFilter([
    'updated_at' => [
      'keyword' => 'this_month'
    ]
  ])
    ->filterQuery()
    ->getQuery();

  expect($query->toSql())->toContain('`users`.`updated_at` between ? and ?');
  expect(normalizeBindings($query->getBindings()))->toBe([
    '2026-03-01 00:00:00',
    '2026-03-31 23:59:59'
  ]);

  Carbon::setTestNow();
});

it('allows child filters to register extra date range columns', function () {
  Carbon::setTestNow('2026-03-26 12:00:00');

  $query = makeBaseTableFilter(
    [
      'verified_at' => [
        'keyword' => 'last_year'
      ]
    ],
    ['verified_at' => 'users.email_verified_at']
  )
    ->filterQuery()
    ->getQuery();

  expect($query->toSql())->toContain('`users`.`email_verified_at` between ? and ?');
  expect(normalizeBindings($query->getBindings()))->toBe([
    '2025-01-01 00:00:00',
    '2025-12-31 23:59:59'
  ]);

  Carbon::setTestNow();
});
