<?php
namespace App\Actions\Event;

use App\Actions\CourseResult\RecordCourseResult;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Student;
use Illuminate\Validation\ValidationException;

class EventResultHandler
{
  /**
   * @param array{
   *  term: string,
   *  for_mid_term?: bool,
   *  academic_session_id: int,
   * } $data
   */
  function __construct(
    private CourseTeacher $courseTeacher,
    private array $data,
    private ?Assessment $assessment = null
  ) {
  }

  function transferEventResult(Event $event)
  {
    foreach ($event->exams as $key => $exam) {
      $this->transferExamResult($exam);
    }
    $event->fill(['transferred_at' => now()])->save();
  }

  function transferExamResult(Exam $studentExam)
  {
    $recordResultObj = null;
    foreach ($studentExam->examCourseables as $key => $examCourseable) {
      $examable = $studentExam->examable;
      if (!($examable instanceof Student)) {
        continue;
      }
      $recordResultObj = $this->recordResult(
        $studentExam->examable,
        $examCourseable
      );
    }
    $recordResultObj?->evaluateResult();
  }

  private function recordResult(
    Student $student,
    ExamCourseable $examCourseable
  ): RecordCourseResult {
    if (
      $examCourseable->courseable->course_id !== $this->courseTeacher->course_id
    ) {
      return throw ValidationException::withMessages([
        'course_teacher_id' => 'Invalid course teacher'
      ]);
    }
    return RecordCourseResult::run(
      [
        'student_id' => $student->id,
        ...$this->data,
        'ass' => [], // ass key is
        ...$this->assessment
          ? [
            'ass' => [$this->assessment->raw_title => $examCourseable->score]
          ]
          : ['exam' => $examCourseable->score]
      ],
      $this->courseTeacher
    );
  }
}
