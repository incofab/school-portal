<?php
namespace App\Actions;

use App\Models\Event;
use App\Models\Exam;
use App\Models\Question;
use App\Support\ExamHandler;
use DB;

class CreateExam
{
  function __construct(private Event $event, private array $post)
  {
  }

  public static function run(Event $event, array $post)
  {
    return (new self($event, $post))->execute();
  }

  private function execute()
  {
    $student = currentInstitutionUser()?->student;
    $institution = currentInstitution();
    $examData = [
      'institution_id' => $institution->id,
      'event_id' => $this->event->id,
      ...$student ? ['student_id' => $student->id] : [],
      ...empty($this->post['external_reference'])
        ? []
        : ['external_reference' => $this->post['external_reference']]
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
      'exam_no' => Exam::generateExamNo()
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
