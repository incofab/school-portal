<?php

namespace App\Support\Faq;

use HTMLPurifier;
use HTMLPurifier_Config;

class FaqContent
{
  public static function cleanHtml(?string $html): string
  {
    if (blank($html)) {
      return '';
    }

    $config = HTMLPurifier_Config::createDefault();
    $config->set(
      'HTML.Allowed',
      implode(',', [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'blockquote',
        'ul',
        'ol',
        'li',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'a[href|title|target|rel]',
        'table',
        'thead',
        'tbody',
        'tr',
        'th',
        'td',
        'span'
      ])
    );
    $config->set('URI.AllowedSchemes', [
      'http' => true,
      'https' => true,
      'mailto' => true
    ]);
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('Cache.DefinitionImpl', null);

    return (new HTMLPurifier($config))->purify($html);
  }

  public static function youtubeVideoId(?string $url): ?string
  {
    if (blank($url)) {
      return null;
    }

    $parts = parse_url(trim($url));

    if (!is_array($parts)) {
      return null;
    }

    $host = strtolower($parts['host'] ?? '');
    $path = trim($parts['path'] ?? '', '/');

    if ($host === 'youtu.be' || str_ends_with($host, '.youtu.be')) {
      $videoId = strtok($path, '/');

      return self::validVideoId($videoId) ? $videoId : null;
    }

    if (!self::isYoutubeHost($host)) {
      return null;
    }

    if (($parts['query'] ?? null) && str_starts_with($path, 'watch')) {
      parse_str($parts['query'], $query);
      $videoId = $query['v'] ?? null;

      return self::validVideoId($videoId) ? $videoId : null;
    }

    foreach (['embed/', 'shorts/', 'live/'] as $prefix) {
      if (str_starts_with($path, $prefix)) {
        $videoId = strtok(substr($path, strlen($prefix)), '/');

        return self::validVideoId($videoId) ? $videoId : null;
      }
    }

    return null;
  }

  public static function youtubeEmbedUrl(?string $url): ?string
  {
    $videoId = self::youtubeVideoId($url);

    return $videoId ? "https://www.youtube.com/embed/{$videoId}" : null;
  }

  public static function isValidYoutubeUrl(?string $url): bool
  {
    return self::youtubeVideoId($url) !== null;
  }

  private static function isYoutubeHost(string $host): bool
  {
    return in_array(
      $host,
      ['youtube.com', 'www.youtube.com', 'm.youtube.com'],
      true
    );
  }

  private static function validVideoId(?string $videoId): bool
  {
    return is_string($videoId) &&
      preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1;
  }
}
