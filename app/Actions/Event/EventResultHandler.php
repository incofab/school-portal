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
  ) {}

  function transferEventResult(Event $event)
  {
    foreach ($event->exams as $key => $exam) {
      $this->transferExamResult($exam);
    }
  }

  function transferExamResult(Exam $studentExam)
  {
    foreach ($studentExam->examCourseables as $key => $examCourseable) {
      $this->recordResult($studentExam->examable, $examCourseable);
    }
  }

  private function recordResult(
    Student $student,
    ExamCourseable $examCourseable
  ) {
    if (
      $examCourseable->courseable->course_id !== $this->courseTeacher->course_id
    ) {
      throw ValidationException::withMessages([
        'course_teacher_id' => 'Invalid course teacher'
      ]);
    }
    RecordCourseResult::run(
      [
        'student_id' => $student->id,
        ...$this->data,
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