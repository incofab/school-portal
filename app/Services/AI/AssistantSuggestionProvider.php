<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\Enums\InstitutionUserType;

/**
 * Optional discoverability prompts.
 *
 * These are UI affordances only. They never define what the assistant accepts:
 * any free-form message is still handled, and a suggestion is never treated as
 * an allow-list entry or as an authorization decision.
 */
class AssistantSuggestionProvider
{
  public function for(AssistantActorContext $context): array
  {
    if ($context->isGuest) {
      return [
        'How do I register my school on EduManager?',
        'What does the institution setup checklist cover?',
        'How do parents get access to their child’s results?',
        'What can EduManager do for a secondary school?'
      ];
    }

    return match ($context->institutionUser?->type) {
      InstitutionUserType::Admin => [
        'What is left on our setup checklist?',
        'Create a class called JSS 1 Silver',
        'How do I publish results for this term?',
        'Add a teacher named Ada Lovelace with email ada@example.com'
      ],
      InstitutionUserType::Teacher => [
        'Which classes and subjects am I assigned to?',
        'How do I record assessment scores?',
        'How do I take attendance for my class?',
        'How do I download a result recording sheet?'
      ],
      InstitutionUserType::Student, InstitutionUserType::Alumni => [
        'Show my published result for this term',
        'How do I use a result checker PIN?',
        'Where can I see my fee payments?',
        'How do I download my transcript?'
      ],
      InstitutionUserType::Guardian => [
        'How do I see my child’s published result?',
        'How do I pay school fees online?',
        'Where do I find payment receipts?',
        'How do I switch between my children?'
      ],
      InstitutionUserType::Accountant => [
        'How do I record a fee payment?',
        'How do I send payment reminders?',
        'Where do I review withdrawals?',
        'How do I export a payment report?'
      ],
      default => [
        'What can you help me with?',
        'How do I find my way around EduManager?',
        'Who do I contact for support?'
      ]
    };
  }
}
