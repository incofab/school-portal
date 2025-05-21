<?php
namespace App\Actions\OfflineMock;

use App\Enums\ExamStatus;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\User;

class MockExamHandler
{
  function __construct(private Event $event)
  {
  }

  static function make(Event $event)
  {
    return new self($event);
  }

  public function makeExamForClassGroup(
    ClassificationGroup $classificationGroup
  ) {
    $arr = [];
    foreach ($classificationGroup->classifications as $key => $classification) {
      $arr[] = $this->makeExamForClass($classification);
    }
    return $arr;
  }

  public function makeExamForClass(Classification $classification)
  {
    $arr = [];
    foreach ($classification->students as $key => $student) {
      $arr[] = $this->makeExam($student->user);
    }
    return $arr;
  }

  public function makeExam(User $user)
  {
    return [
      'exam_no' => 'generate',
      'status' => ExamStatus::Pending->value,
      'start_time' => null,
      'end_time' => null,
      'pause_time' => null,
      'attempts' => null,
      'num_of_questions' => 0,
      'time_remaining' => 0,
      'score' => 0,
      'event_id' => $this->event->id,
      'student' => [
        'id' => $user->id,
        'code' => $user->id,
        'firstname' => $user->first_name,
        'lastname' => $user->last_name
      ],
      'exam_courses' => $this->event->eventCourseables
        ->map(function (EventCourseable $eventCourseable) {
          $courseSession = $eventCourseable->courseable;
          $course = $courseSession->course;
          return [
            'course_session_id' => $eventCourseable->courseable_id,
            'score' => 0,
            'status' => ExamStatus::Pending->value,
            'num_of_questions' => 0,
            'course_code' => $course->code,
            'session' => $courseSession->session,
            'course_session' => [
              'id' => $courseSession->id,
              'session' => $courseSession->session,
              'course_id' => $course->id,
              'general_instructions' => $course->general_instructions,
              'course' => [
                'id' => $course->id,
                'course_code' => $course->code,
                'course_title' => $course->title
              ]
            ]
          ];
        })
        ->toArray()
    ];
  }
}
