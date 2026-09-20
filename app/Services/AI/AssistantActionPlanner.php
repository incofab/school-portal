<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use Illuminate\Support\Str;

class AssistantActionPlanner
{
  /**
   * Words that end a spoken name, so "named Ada Lovelace with email ..." does
   * not absorb the rest of the sentence into the name.
   */
  private const NAME_STOP_WORDS = 'with\\b|and\\b|email\\b|e-mail\\b|phone\\b|role\\b|gender\\b|as\\b|in\\b|to\\b|for\\b';

  public function plan(string $message, AssistantActorContext $context): ?array
  {
    $lower = Str::lower($message);

    if ($this->isExplicitlyDeclined($message)) {
      return null;
    }

    if (
      (Str::contains($lower, ['send notification', 'send a notification']) ||
        preg_match(
          '/\bnotify\s+(?:the\s+)?(?:class|group|students|parents|staff)\b/i',
          $message
        )) &&
      !Str::contains($lower, ['how', 'what', 'where', 'why'])
    ) {
      return [
        'tool' => 'send_institution_notification',
        'arguments' => $this->notificationArguments($message)
      ];
    }

    if (
      preg_match(
        '/\b(create|add|new|set up|setup)\b.*\b(class|classroom)\b/i',
        $message
      )
    ) {
      return [
        'tool' => 'create_class',
        'arguments' => $this->classArguments($message)
      ];
    }

    if (
      preg_match(
        '/\b(update|rename|edit|change)\b.*\b(class|classroom)\b/i',
        $message
      )
    ) {
      return [
        'tool' => 'update_class',
        'arguments' => $this->updatedClassArguments($message)
      ];
    }

    if (
      preg_match(
        '/\b(create|add|new|set up|setup)\b.*\b(subject|course)\b/i',
        $message
      )
    ) {
      return [
        'tool' => 'create_subject',
        'arguments' => $this->subjectArguments($message)
      ];
    }

    // Staff intents are checked last so a class or subject request that merely
    // mentions a form teacher is not mistaken for a staff request.
    if (
      preg_match(
        '/\b(add|create|register|onboard|invite)\b.*\b(staff|teacher|tutor|employee|bursar|accountant)\b/i',
        $message
      )
    ) {
      return [
        'tool' => 'create_staff',
        'arguments' => $this->staffArguments($message)
      ];
    }

    if (
      preg_match(
        '/\b(update|edit|change|modify)\b.*\b(staff|teacher|tutor|employee)\b/i',
        $message
      )
    ) {
      return [
        'tool' => 'update_staff',
        'arguments' => $this->staffArguments($message)
      ];
    }

    return null;
  }

  private function isExplicitlyDeclined(string $message): bool
  {
    return (bool) preg_match(
      '/\b(?:do\s+not|don\'t|dont|never|no\s+need\s+to)\b[^.!?]{0,80}\b(?:send|notify|create|add|update|rename|edit|change|register|invite)\b/i',
      $message
    );
  }

  /**
   * Extract the staff details the administrator stated explicitly. Anything
   * that cannot be read from the message is left out so the action tool asks
   * for it instead of guessing.
   */
  private function staffArguments(string $message): array
  {
    $arguments = [];

    if (preg_match('/[\w.+-]+@[\w-]+\.[\w.-]+/', $message, $matches)) {
      $arguments['email'] = Str::lower(
        trim($matches[0], " \t\n\r\0\x0B,.:;\"'")
      );
    }

    if (
      preg_match(
        '/\bname[d]?\s*[:=]?\s*["\']?((?:[A-Za-z][A-Za-z\'\-]*)(?:\s+(?!' .
          self::NAME_STOP_WORDS .
          ')[A-Za-z][A-Za-z\'\-]*){1,2})["\']?/i',
        $message,
        $matches
      )
    ) {
      $names = preg_split('/\s+/', trim($matches[1]));
      $arguments['first_name'] = array_shift($names);
      $arguments['last_name'] = array_pop($names) ?? null;

      if ($names) {
        $arguments['other_names'] = implode(' ', $names);
      }
    }

    if (
      preg_match(
        '/\brole\s*[:=]?\s*["\']?([A-Za-z][A-Za-z ]*?)["\']?(?=\s*(?:[,.]|\bwith\b|\band\b|\bemail\b|\bphone\b|$))/i',
        $message,
        $matches
      )
    ) {
      $arguments['role'] = trim($matches[1]);
    } elseif (
      preg_match(
        '/\b(teacher|admin|bursar|accountant|librarian)\b/i',
        $message,
        $matches
      )
    ) {
      $arguments['role'] = Str::title($matches[1]);
    }

    if (
      preg_match(
        '/\bphone\s*[:=]?\s*["\']?(\+?[\d][\d\s-]{5,19})/i',
        $message,
        $matches
      )
    ) {
      $arguments['phone'] = trim($matches[1]);
    }

    if (
      preg_match('/\bgender\s*[:=]?\s*(male|female)\b/i', $message, $matches)
    ) {
      $arguments['gender'] = Str::lower($matches[1]);
    }

    if (
      preg_match(
        '/\b(?:staff|institution user)\s*(?:id)?\s*[:#]\s*(\d+)\b/i',
        $message,
        $matches
      )
    ) {
      $arguments['institution_user_id'] = (int) $matches[1];
    }

    return $arguments;
  }

  private function classArguments(string $message): array
  {
    $arguments = [];

    if (
      preg_match(
        '/\b(?:class|classroom)\s+["\']([^"\']+)["\']/i',
        $message,
        $matches
      )
    ) {
      $arguments['title'] = trim($matches[1]);
    } elseif (
      preg_match(
        '/\b(?:class|classroom)\s+(.+?)(?=\s+(?:in|under|group|with|description|form teacher)\b|$)/i',
        $message,
        $matches
      )
    ) {
      $arguments['title'] = trim($matches[1], " \t\n\r\0\x0B,.:");
    }

    if (
      preg_match(
        '/\b(?:classification\s+)?group(?:\s+id)?\s*[:#]?\s*(\d+)\b/i',
        $message,
        $matches
      )
    ) {
      $arguments['classification_group_id'] = (int) $matches[1];
    }

    if (
      preg_match(
        '/\bform\s+teacher\s*(?:id)?\s*[:#]?\s*(\d+)\b/i',
        $message,
        $matches
      )
    ) {
      $arguments['form_teacher_id'] = (int) $matches[1];
    }

    if (
      preg_match(
        '/\bdescription\s*[:=]\s*["\']?(.+?)["\']?$/i',
        $message,
        $matches
      )
    ) {
      $arguments['description'] = trim($matches[1]);
    }

    return $arguments;
  }

  private function updatedClassArguments(string $message): array
  {
    $arguments = [];

    if (preg_match('/\b(?:class|classroom)\s+(\d+)\b/i', $message, $matches)) {
      $arguments['classification_id'] = (int) $matches[1];
    }

    if (
      preg_match(
        '/\b(?:to|as|title)\s+["\']([^"\']+)["\']/i',
        $message,
        $matches
      )
    ) {
      $arguments['title'] = trim($matches[1]);
    } elseif (preg_match('/\b(?:to|as|title)\s+(.+?)$/i', $message, $matches)) {
      $arguments['title'] = trim($matches[1], " \t\n\r\0\x0B,.:");
    }

    return $arguments;
  }

  private function subjectArguments(string $message): array
  {
    $arguments = [];

    if (
      preg_match(
        '/\b(?:subject|course)\s+["\']([^"\']+)["\']/i',
        $message,
        $matches
      )
    ) {
      $arguments['title'] = trim($matches[1]);
    } elseif (
      preg_match(
        '/\b(?:subject|course)\s+(.+?)(?=\s+code\b|$)/i',
        $message,
        $matches
      )
    ) {
      $arguments['title'] = trim($matches[1], " \t\n\r\0\x0B,.:");
    }

    if (
      preg_match(
        '/\bcode\s*[:=]?\s*["\']?([a-z0-9_-]+)["\']?/i',
        $message,
        $matches
      )
    ) {
      $arguments['code'] = trim($matches[1]);
    }

    if (
      preg_match(
        '/\bdescription\s*[:=]\s*["\']?(.+?)["\']?$/i',
        $message,
        $matches
      )
    ) {
      $arguments['description'] = trim($matches[1]);
    }

    return $arguments;
  }

  private function notificationArguments(string $message): array
  {
    $arguments = [];

    if (
      preg_match('/\btitle\s*[:=]\s*["\']([^"\']+)["\']/i', $message, $matches)
    ) {
      $arguments['title'] = trim($matches[1]);
    }

    if (preg_match('/\bbody\s*[:=]\s*["\'](.+?)["\']/i', $message, $matches)) {
      $arguments['body'] = trim($matches[1]);
    }

    if (
      preg_match('/\b(?:class|classification)\s+(\d+)\b/i', $message, $matches)
    ) {
      $arguments['target_type'] = 'classification';
      $arguments['target_id'] = (int) $matches[1];
    } elseif (
      preg_match(
        '/\b(?:class group|classification group)\s+(\d+)\b/i',
        $message,
        $matches
      )
    ) {
      $arguments['target_type'] = 'classification-group';
      $arguments['target_id'] = (int) $matches[1];
    }

    return $arguments;
  }
}
