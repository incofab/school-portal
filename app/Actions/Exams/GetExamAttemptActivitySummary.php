<?php

namespace App\Actions\Exams;

use App\Models\Event;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\TheoryQuestion;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class GetExamAttemptActivitySummary
{
  public const ACTIVE_THRESHOLD_SECONDS = 90;

  public function execute(Event $event): array
  {
    $exams = Exam::query()
      ->where('event_id', $event->id)
      ->where('institution_id', $event->institution_id)
      ->with([
        'examable' => function (MorphTo $morphTo) {
          $morphTo->morphWith([Student::class => ['user', 'classification']]);
        },
        'lastQuestionable',
        'examCourseables:id,exam_id,courseable_type,courseable_id'
      ])
      ->withCount([
        'questionAttempts as answered_questions_count' => fn(
          $query
        ) => $query->where('is_answered', true)
      ])
      ->latest('id')
      ->get();

    $totals = $this->questionTotals($exams);
    $threshold = now()->subSeconds(self::ACTIVE_THRESHOLD_SECONDS);

    return [
      'active_threshold_seconds' => self::ACTIVE_THRESHOLD_SECONDS,
      'attempts' => $exams
        ->map(function (Exam $exam) use ($totals, $threshold) {
          $totalQuestions = $exam->examCourseables->sum(
            fn($courseable) => $totals[
              $courseable->courseable_type . ':' . $courseable->courseable_id
            ] ?? 0
          );
          $answeredQuestions = (int) $exam->answered_questions_count;
          $lastSeen = collect([$exam->last_ping_at, $exam->last_activity_at])
            ->filter()
            ->max();
          $activityStatus = $exam->submitted_at
            ? 'submitted'
            : ($lastSeen && $lastSeen->greaterThanOrEqualTo($threshold)
              ? 'active'
              : ($exam->start_time
                ? 'idle'
                : 'not_started'));

          return [
            'exam_id' => $exam->id,
            'exam_no' => $exam->exam_no,
            'student_name' => $exam->getExamableName(),
            'student_code' =>
              $exam->examable instanceof Student
                ? $exam->examable->full_code
                : null,
            'status' => $exam->status?->value ?? $exam->status,
            'activity_status' => $activityStatus,
            'started_at' => $exam->start_time?->toISOString(),
            'last_activity_at' => $exam->last_activity_at?->toISOString(),
            'last_ping_at' => $exam->last_ping_at?->toISOString(),
            'answered_questions_count' => $answeredQuestions,
            'total_questions_count' => $totalQuestions,
            'progress_percentage' =>
              $totalQuestions > 0
                ? round(($answeredQuestions / $totalQuestions) * 100, 1)
                : 0,
            'current_question_index' => $exam->current_question_index,
            'last_question' => $exam->lastQuestionable
              ? [
                'id' => $exam->lastQuestionable->id,
                'type' => $exam->last_questionable_type,
                'question_no' => $exam->lastQuestionable->question_no ?? null
              ]
              : null,
            'submitted_at' => $exam->submitted_at?->toISOString()
          ];
        })
        ->values()
        ->toArray()
    ];
  }

  private function questionTotals(Collection $exams): array
  {
    $courseables = $exams
      ->flatMap(fn(Exam $exam) => $exam->examCourseables)
      ->unique(
        fn($courseable) => $courseable->courseable_type .
          ':' .
          $courseable->courseable_id
      )
      ->values();

    $totals = [];

    if ($courseables->isEmpty()) {
      return $totals;
    }

    foreach ([Question::class, TheoryQuestion::class] as $model) {
      $model
        ::query()
        ->selectRaw('courseable_type, courseable_id, count(*) as aggregate')
        ->where(function ($query) use ($courseables) {
          foreach ($courseables as $courseable) {
            $query->orWhere(function ($inner) use ($courseable) {
              $inner
                ->where('courseable_type', $courseable->courseable_type)
                ->where('courseable_id', $courseable->courseable_id);
            });
          }
        })
        ->groupBy('courseable_type', 'courseable_id')
        ->get()
        ->each(function ($row) use (&$totals) {
          $key = $row->courseable_type . ':' . $row->courseable_id;
          $totals[$key] = ($totals[$key] ?? 0) + (int) $row->aggregate;
        });
    }

    return $totals;
  }
}
