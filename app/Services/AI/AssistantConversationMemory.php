<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AssistantConversationMemory
{
    /**
     * Interpret a turn using only user-provided conversation context.
     *
     * The extracted entities are hints for continuity, not verified records.
     */
    public function interpret(
        AiConversation $conversation,
        string $message
    ): array {
        $previousTopic = $conversation->topic;
        $detectedTopic = $this->detectTopic($message);
        $topic = $detectedTopic ?? ($previousTopic ?? 'general');
        $topicChanged =
          $previousTopic !== null &&
          $detectedTopic !== null &&
          $detectedTopic !== $previousTopic;
        $extracted = $this->extractEntities($message);
        $existing = $topicChanged ? [] : $conversation->entities ?? [];
        $entities = array_merge($existing, $extracted);
        $correction = $this->isCorrection($message);

        return [
            'topic' => $topic,
            'entities' => $entities,
            'correction' => $correction,
            'topic_changed' => $topicChanged,
            'clarification' => $this->clarificationFor($message, $topic, $entities),
        ];
    }

    public function apply(
        AiConversation $conversation,
        array $interpretation
    ): void {
        $conversation
            ->forceFill([
                'topic' => $interpretation['topic'] ?? null,
                'entities' => $interpretation['entities'] ?? [],
            ])
            ->save();
    }

    public function summarizeIfNeeded(AiConversation $conversation): void
    {
        $messageCount = $conversation->messages()->count();

        if ($messageCount < config('ai.assistant.summary_after_messages', 8)) {
            return;
        }

        $messages = $conversation
            ->messages()
            ->latest('id')
            ->limit(config('ai.assistant.summary_message_sample', 20))
            ->get()
            ->sortBy('id');

        $userRequests = $messages
            ->where('role', 'user')
            ->pluck('content')
            ->map(fn (string $content) => Str::limit($this->singleLine($content), 260))
            ->take(-4)
            ->implode(' | ');
        $assistantGuidance = $messages
            ->where('role', 'assistant')
            ->pluck('content')
            ->map(fn (string $content) => Str::limit($this->singleLine($content), 260))
            ->take(-3)
            ->implode(' | ');
        $entityLines = collect($conversation->entities ?? [])
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode(', ');

        $parts = [
            'Topic: '.($conversation->topic ?: 'general'),
            $entityLines !== '' ? "Known context: {$entityLines}" : null,
            $userRequests !== '' ? "Recent user requests: {$userRequests}" : null,
            $assistantGuidance !== ''
              ? "Recent assistant guidance: {$assistantGuidance}"
              : null,
        ];

        $conversation
            ->forceFill([
                'summary' => Str::limit(
                    implode("\n", array_filter($parts)),
                    config('ai.assistant.summary_max_chars', 2000),
                    '...'
                ),
                'summary_updated_at' => Carbon::now(),
            ])
            ->save();
    }

    public function promptContext(AiConversation $conversation): string
    {
        $maxChars = max(
            (int) config('ai.assistant.max_context_chars', 12000),
            1000
        );
        $summary = Str::limit((string) $conversation->summary, 2000);
        $entities = collect($conversation->entities ?? [])
            ->map(fn ($value, $key) => "- {$key}: {$value}")
            ->implode("\n");
        $messages = $conversation
            ->messages()
            ->latest('id')
            ->limit(config('ai.assistant.max_context_messages', 12))
            ->get()
            ->sortBy('id');

        $selected = [];
        $used = 0;
        foreach ($messages->reverse() as $message) {
            $line = strtoupper($message->role).":\n{$message->content}";
            $lineLength = mb_strlen($line) + 10;

            if ($selected !== [] && $used + $lineLength > $maxChars) {
                break;
            }

            $selected[] = $line;
            $used += $lineLength;
        }

        $transcript = implode("\n\n---\n\n", array_reverse($selected));
        $context = "Conversation memory (untrusted, user-provided or inferred):\n";
        $context .= $summary !== '' ? "Summary:\n{$summary}\n" : '';
        $context .= $entities !== '' ? "Known entities:\n{$entities}\n" : '';
        $context .= "Recent turns:\n{$transcript}";

        return Str::limit($context, $maxChars, '...');
    }

    private function detectTopic(string $message): ?string
    {
        $topics = [
            'results' => [
                'result',
                'score',
                'grade',
                'mark',
                'exam',
                'assessment',
                'transcript',
                'report card',
            ],
            'attendance' => ['attendance', 'absent', 'absence', 'present'],
            'fees' => ['fee', 'fees', 'payment', 'invoice', 'balance', 'bursary'],
            'classes' => ['class', 'subject', 'course', 'teacher'],
            'students' => ['student', 'learner', 'pupil', 'admission'],
            'onboarding' => [
                'setup',
                'onboarding',
                'register',
                'registration',
                'institution',
            ],
        ];

        foreach ($topics as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains(Str::lower($message), $keyword)) {
                    return $topic;
                }
            }
        }

        return null;
    }

    private function extractEntities(string $message): array
    {
        $entities = [];

        if (preg_match_all('/\b(20\d{2}\s*\/\s*20\d{2})\b/', $message, $matches)) {
            $session = end($matches[1]);
            $entities['academic_session'] = preg_replace('/\s+/', '', $session);
        }

        if (preg_match_all('/\b(first|second|third)\s+term\b/i', $message, $matches)) {
            $term = end($matches[1]);
            $entities['term'] = ucfirst(Str::lower($term)).' Term';
        } elseif (preg_match_all('/\bterm\s*([123])\b/i', $message, $matches)) {
            $term = end($matches[1]);
            $entities['term'] = [
                '1' => 'First Term',
                '2' => 'Second Term',
                '3' => 'Third Term',
            ][$term];
        } elseif (preg_match('/\b(last|current|this)\s+term\b/i', $message, $matches)) {
            $entities['term'] = ucfirst(Str::lower($matches[1])).' Term';
        }

        if (
            preg_match(
                '/\b(jss|sss|primary|basic|grade|year)\s*-?\s*\d+[a-z]?(?:\s+[a-z][a-z0-9-]*)?\b/i',
                $message,
                $matches
            )
        ) {
            $entities['class'] = preg_replace('/\s+/', ' ', trim($matches[0]));
        }

        if (
            preg_match(
                '/\bsubject\s*(?:is|:)?\s*([a-z][a-z &-]{1,40})/i',
                $message,
                $matches
            )
        ) {
            $entities['subject'] = trim($matches[1], ' .,!?:;');
        }

        if (
            preg_match(
                '/\bstudent\s*(?:named|called|:)?\s+([a-z][a-z -]{1,50})/i',
                $message,
                $matches
            )
        ) {
            $entities['student'] = trim($matches[1], ' .,!?:;');
        }

        return $entities;
    }

    private function clarificationFor(
        string $message,
        string $topic,
        array $entities
    ): ?array {
        if (! in_array($topic, ['results', 'attendance', 'fees'], true)) {
            return null;
        }

        $lower = Str::lower($message);
        $isLookup = preg_match(
            '/\b(show|view|check|compare|find|list|give|tell|what(?:\s+is|\'s)?)\b/',
            $lower
        );

        if (
            ! $isLookup ||
            (isset($entities['academic_session']) && isset($entities['term']))
        ) {
            return null;
        }

        $missing = array_values(
            array_filter([
                ! isset($entities['academic_session']) ? 'academic_session' : null,
                ! isset($entities['term']) ? 'term' : null,
            ])
        );
        $question = count($missing) === 2
            ? 'Which academic session and term should I use? For example: “2025/2026, First Term.”'
            : (in_array('academic_session', $missing, true)
                ? 'Which academic session should I use? For example: “2025/2026.”'
                : 'Which term should I use? For example: “First Term.”');

        return [
            'question' => $question,
            'required' => $missing,
            'topic' => $topic,
        ];
    }

    private function isCorrection(string $message): bool
    {
        return (bool) preg_match(
            '/\b(actually|correction|i meant|not\s+.+,|change\s+.+\s+to)\b/i',
            $message
        );
    }

    private function singleLine(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value));
    }
}
