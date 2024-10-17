<?php
namespace App\Actions\Exam;

use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\ExamStatus;
use App\Models\Classification;
use App\Models\CourseTeacher;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Question;
use App\Models\Student;
use App\Support\ExamHandler;
use DB;

class TransferExamResult
{
  private Student $student;
  function __construct(
    private Exam $studentExam,
    private Classification $classification,
    private CourseTeacher $courseTeacher
  ) {
    $this->student = $studentExam->examable;
  }

  function execute()
  {
    $institution = currentInstitution();
  }

  private function recordResult(ExamCourseable $examCourseable)
  {
    $course = $$examCourseable->courseable->course;
    RecordCourseResult::run([], $this->courseTeacher);
  }
}
