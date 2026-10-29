<?php

namespace App\Actions\Exams;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamQuestionAttempt;
use App\Models\Question;
use App\Models\TheoryQuestion;
use App\Support\ExamHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordExamQuestionAttempt
{
  public function execute(
    Exam $exam,
    array $attempts,
    ?int $currentQuestionIndex = null
  ): Collection {
    abort_if(
      $exam->submitted_at || $exam->status === ExamStatus::Ended,
      409,
      'This exam has already been submitted.'
    );

    ExamHandler::make($exam)->canRun();

    $records = collect($attempts)
      ->map(
        fn($answer, $key) => $this->resolveQuestion(
          $exam,
          (string) $key,
          $answer
        )
      )
      ->filter()
      ->values();

    abort_if(
      $records->count() !== count($attempts),
      422,
      'One or more questions do not belong to this exam.'
    );

    if ($records->isEmpty()) {
      return $records;
    }

    $now = now();
    $upserts = $records
      ->map(
        fn(array $record) => [
          'exam_id' => $exam->id,
          'institution_id' => $exam->institution_id,
          'questionable_type' => $record['question']->getMorphClass(),
          'questionable_id' => $record['question']->id,
          'answer' => $record['answer'],
          'is_answered' => filled($record['answer']),
          'answered_at' => filled($record['answer']) ? $now : null,
          'created_at' => $now,
          'updated_at' => $now
        ]
      )
      ->toArray();

    DB::transaction(function () use (
      $exam,
      $records,
      $upserts,
      $now,
      $currentQuestionIndex
    ) {
      ExamQuestionAttempt::query()->upsert(
        $upserts,
        ['exam_id', 'questionable_type', 'questionable_id'],
        ['answer', 'is_answered', 'answered_at', 'updated_at']
      );

      $lastQuestion = $records->last()['question'];

      $exam
        ->newQuery()
        ->whereKey($exam->id)
        ->whereNull('submitted_at')
        ->update([
          'last_activity_at' => $now,
          'last_ping_at' => $now,
          'last_questionable_type' => $lastQuestion->getMorphClass(),
          'last_questionable_id' => $lastQuestion->id,
          'current_question_index' => $currentQuestionIndex
        ]);
    });

    return $records;
  }

  private function resolveQuestion(
    Exam $exam,
    string $key,
    mixed $answer
  ): ?array {
    $isTheory = str_starts_with($key, 'theory-');
    $questionId = (int) ($isTheory ? substr($key, 7) : $key);
    $model = $isTheory ? TheoryQuestion::class : Question::class;

    if ($questionId < 1 || (!is_string($answer) && !is_numeric($answer))) {
      return null;
    }

    /** @var Model|null $question */
    $question = $model
      ::query()
      ->whereKey($questionId)
      ->where('institution_id', $exam->institution_id)
      ->whereExists(function ($query) use ($exam) {
        $query
          ->selectRaw('1')
          ->from('exam_courseables')
          ->whereColumn('exam_courseables.courseable_type', 'courseable_type')
          ->whereColumn('exam_courseables.courseable_id', 'courseable_id')
          ->where('exam_courseables.exam_id', $exam->id);
      })
      ->first();

    return $question
      ? [
        'question' => $question,
        'answer' => trim((string) $answer)
      ]
      : null;
  }
}
