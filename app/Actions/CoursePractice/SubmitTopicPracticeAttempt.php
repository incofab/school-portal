<?php

namespace App\Actions\CoursePractice;

use App\Models\InstitutionUser;
use App\Models\TopicPracticeAttempt;
use Illuminate\Support\Facades\DB;

class SubmitTopicPracticeAttempt
{
  public function __construct(
    private InstitutionUser $institutionUser,
    private TopicPracticeAttempt $attempt,
    private array $answers
  ) {
  }

  public static function run(
    InstitutionUser $institutionUser,
    TopicPracticeAttempt $attempt,
    array $answers
  ): array {
    return (new self($institutionUser, $attempt, $answers))->execute();
  }

  public function execute(): array
  {
    abort_unless($this->institutionUser->isStudent(), 403);
    $student = $this->institutionUser->student;
    abort_unless($student, 403, 'Student profile not found.');
    abort_unless($this->attempt->student_id === $student->id, 404);

    return DB::transaction(function () {
      $attempt = $this->attempt->freshWithLockForUpdate();

      $summary = $attempt
        ->summary()
        ->lockForUpdate()
        ->firstOrFail();

      $questions = $attempt->questions ?? [];
      $score = 0;
      $answeredQuestionsCount = 0;

      foreach ($questions as $index => $question) {
        $selectedAnswer = $this->answers[$index] ?? null;
        if ($selectedAnswer) {
          $answeredQuestionsCount++;
        }

        if (
          $selectedAnswer &&
          $this->normalizeAnswer($selectedAnswer) ===
            $this->normalizeAnswer($question['answer'] ?? '')
        ) {
          $score++;
        }
      }

      $questionsCount = count($questions);
      $percentage =
        $questionsCount > 0 ? round(($score / $questionsCount) * 100, 2) : 0;

      $attempt
        ->forceFill([
          'answers' => $this->answers,
          'score' => $score,
          'questions_count' => $questionsCount,
          'answered_questions_count' => $answeredQuestionsCount,
          'percentage' => $percentage,
          'submitted_at' => now()
        ])
        ->save();

      $isBest = $percentage >= (float) $summary->best_percentage;
      $summary
        ->forceFill([
          'latest_score' => $score,
          'latest_questions_count' => $questionsCount,
          'latest_percentage' => $percentage,
          'last_submitted_at' => now(),
          'best_score' => $isBest ? $score : $summary->best_score,
          'best_questions_count' => $isBest
            ? $questionsCount
            : $summary->best_questions_count,
          'best_percentage' => $isBest ? $percentage : $summary->best_percentage
        ])
        ->save();

      return [
        'attempt' => $attempt->fresh(),
        'summary' => $summary->fresh()
      ];
    });
  }

  private function normalizeAnswer(string $answer): string
  {
    return strtolower(str_replace('option_', '', trim($answer)));
  }
}
