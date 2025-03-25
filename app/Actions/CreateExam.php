<?php
namespace App\Actions;

use App\Enums\ExamStatus;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Question;
use App\Support\ExamHandler;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CreateExam
{
  function __construct(private Event $event, private array $post)
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

  function execute()
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
      $questionsCount = Question::query()
        ->where(
          collect($courseable)
            ->only(['courseable_id', 'courseable_type'])
            ->toArray()
        )
        ->count();
      $exam
        ->examCourseables()
        ->firstOrCreate([
          ...$courseable,
          'num_of_questions' => $questionsCount
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
