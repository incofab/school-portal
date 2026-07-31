<?php

use App\Models\PaymentReference;
use App\Support\UITableFilters\PaymentReferenceUITableFilters;

it('applies direct payment reference filters', function () {
  $query = PaymentReferenceUITableFilters::make(
    [
      'institution_id' => 5,
      'status' => 'pending',
      'merchant' => 'paystack',
      'purpose' => 'fee',
      'reference' => 'INV-100',
      'date_from' => '2026-07-01',
      'date_to' => '2026-07-31',
      'sortKey' => 'amount',
      'sortDir' => 'desc'
    ],
    PaymentReference::query()->select('payment_references.*')
  )
    ->filterQuery()
    ->getQuery();

  expect($query->toSql())
    ->toContain('`payment_references`.`institution_id` = ?')
    ->toContain('`payment_references`.`status` = ?')
    ->toContain('`payment_references`.`merchant` = ?')
    ->toContain('`payment_references`.`purpose` = ?')
    ->toContain('`payment_references`.`reference` like ?')
    ->toContain('date(`payment_references`.`created_at`) >= ?')
    ->toContain('date(`payment_references`.`created_at`) <= ?')
    ->toContain('order by `payment_references`.`amount` desc');

  expect($query->getBindings())->toBe([
    5,
    'pending',
    'paystack',
    'fee',
    '%INV-100%',
    '2026-07-01',
    '2026-07-31'
  ]);
});

it('applies general search to reference purpose and merchant', function () {
  $query = PaymentReferenceUITableFilters::make(
    ['search' => 'wallet'],
    PaymentReference::query()->select('payment_references.*')
  )
    ->filterQuery()
    ->getQuery();

  expect($query->toSql())
    ->toContain('`payment_references`.`reference` like ?')
    ->toContain('`payment_references`.`purpose` like ?')
    ->toContain('`payment_references`.`merchant` like ?');
  expect($query->getBindings())->toBe([
    '%wallet%',
    '%wallet%',
    '%wallet%'
  ]);
});

it('applies the standard table drawer date range filter', function () {
  $query = PaymentReferenceUITableFilters::make(
    [
      'created_at' => [
        'date_from' => '2026-07-01',
        'date_to' => '2026-07-31'
      ]
    ],
    PaymentReference::query()->select('payment_references.*')
  )
    ->filterQuery()
    ->getQuery();

  expect($query->toSql())->toContain(
    '`payment_references`.`created_at` between ? and ?'
  );
  expect($query->getBindings()[0]->toDateTimeString())->toBe(
    '2026-07-01 00:00:00'
  );
  expect($query->getBindings()[1]->toDateTimeString())->toBe(
    '2026-07-31 23:59:59'
  );
});
