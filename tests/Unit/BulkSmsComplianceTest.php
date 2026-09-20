<?php

use App\Services\Messaging\BulkSmsCompliance;

function bulkSmsCompliance(array $words = ['code']): BulkSmsCompliance
{
  return new BulkSmsCompliance(
    blockedWords: $words,
    blockedSenderIds: ['FBI', 'Interp0l'],
    blockedCharacters: ['@'],
    maxSenderLength: 11,
    maxMessageLength: 1530
  );
}

it('rejects blocked words without rewriting the message', function () {
  $message = 'Your code is ready';
  $result = bulkSmsCompliance()->validateMessageAndSender(
    $message,
    'EduManager'
  );

  expect($result->isNotSuccessful())
    ->toBeTrue()
    ->and($result['violations']['blocked_words'])
    ->toContain('code')
    ->and($message)
    ->toBe('Your code is ready');
});

it('detects common lookalike forms during compliance checks', function () {
  $result = bulkSmsCompliance()->validateMessageAndSender(
    'Your c0de is ready',
    'EduManager'
  );

  expect($result->isNotSuccessful())->toBeTrue();
});

it('rejects blocked or malformed sender IDs', function () {
  expect(bulkSmsCompliance()->validateMessageAndSender('Hello', 'FBI'))
    ->isNotSuccessful()
    ->toBeTrue();

  expect(bulkSmsCompliance()->validateMessageAndSender('Hello', 'Bad Sender'))
    ->isNotSuccessful()
    ->toBeTrue();
});

it(
  'normalizes valid Nigerian recipients for the provider payload',
  function () {
    $result = bulkSmsCompliance()->validate(
      'Hello',
      'EduManager',
      '08012345678,+2348012345679'
    );

    expect($result->isSuccessful())
      ->toBeTrue()
      ->and($result['recipients'])
      ->toBe('2348012345678,2348012345679');
  }
);

it('rejects invalid recipient numbers', function () {
  $result = bulkSmsCompliance()->validate(
    'Hello',
    'EduManager',
    'not-a-phone-number'
  );

  expect($result->isNotSuccessful())
    ->toBeTrue()
    ->and($result['violations']['recipients'])
    ->toContain('not-a-phone-number');
});
