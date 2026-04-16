<?php

namespace App\Actions;

use App\Enums\ExamStatus;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Question;
use App\Models\TheoryQuestion;
use App\Support\ExamHandler;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CreateExam
{
  /**
   * @param array {
   *  examable_type: string,
   *  examable_id: int,
   * } $post
   */
  public function __construct(private Event $event, private array $post)
  {
  }

  public static function make(
    Event $event,
    Model $examable,
    Collection $eventCourseables,
    array $post
  ) {
    return new self($event, [
      'examable_type' => $examable->getMorphClass(),
      'examable_id' => $examable->id,
      'courseables' => $eventCourseables
        ->map(
          fn($item) => [
            'courseable_id' => $item->courseable_id,
            'courseable_type' => $item->courseable_type
          ]
        )
        ->toArray(),
      ...$post
    ]);
  }

  public static function run(Event $event, array $post)
  {
    return (new self($event, $post))->execute();
  }

  public function execute()
  {
    $examData = [
      'institution_id' => $this->event->institution_id,
      'event_id' => $this->event->id,
      'examable_type' => $this->post['examable_type'],
      'examable_id' => $this->post['examable_id']
    ];

    $checkExam = $this->event
      ->exams()
      ->getQuery()
      ->where($examData)
      ->first();

    if ($checkExam) {
      return $this->onExamCreated($checkExam);
    }

    DB::beginTransaction();
    $exam = $this->event->exams()->firstOrCreate($examData, [
      'exam_no' => Exam::generateExamNo(),
      'status' => ExamStatus::Pending
    ]);

    foreach ($this->post['courseables'] as $key => $courseable) {
      $courseableData = collect($courseable)
        ->only(['courseable_id', 'courseable_type'])
        ->toArray();
      $questionsCount = Question::query()
        ->where($courseableData)
        ->count();
      $theoryQuestionQuery = TheoryQuestion::query()->where($courseableData);
      $theoryQuestionsCount = (clone $theoryQuestionQuery)->count();
      $theoryMaxScore = (clone $theoryQuestionQuery)->sum('marks');
      $exam->examCourseables()->updateOrCreate($courseableData, [
        'num_of_questions' => $questionsCount,
        'theory_score' => 0,
        'theory_max_score' => $theoryMaxScore,
        'theory_num_of_questions' => $theoryQuestionsCount,
        'theory_question_scores' => null,
        'theory_evaluated' => $theoryQuestionsCount === 0
      ]);
    }
    DB::commit();

    return $this->onExamCreated($exam);
  }

  private function onExamCreated(Exam $exam)
  {
    if (!empty($this->post['start_now'])) {
      ExamHandler::make($exam)->startExam();
    }

    return $exam;
  }
}
