<?php

use App\Support\Faq\FaqContent;

it('extracts youtube ids from supported faq video urls', function (
  string $url
) {
  expect(FaqContent::youtubeVideoId($url))->toBe('dQw4w9WgXcQ');
  expect(FaqContent::youtubeEmbedUrl($url))->toBe(
    'https://www.youtube.com/embed/dQw4w9WgXcQ'
  );
})->with([
  'watch url' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
  'youtu be url' => ['https://youtu.be/dQw4w9WgXcQ'],
  'shorts url' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
  'embed url' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
  'live url' => ['https://www.youtube.com/live/dQw4w9WgXcQ']
]);

it('rejects unsupported youtube urls', function () {
  expect(
    FaqContent::youtubeVideoId('https://example.com/watch?v=dQw4w9WgXcQ')
  )->toBeNull();
});

it('cleans unsafe faq html while preserving rich text', function () {
  $html = FaqContent::cleanHtml(
    '<h2>Title</h2><p onclick="bad()">Answer <strong>bold</strong></p><script>alert(1)</script>'
  );

  expect($html)
    ->toContain('<h2>Title</h2>')
    ->toContain('<strong>bold</strong>')
    ->not->toContain('onclick')
    ->not->toContain('<script>');
});
