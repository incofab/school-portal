<?php
namespace App\Actions\OfflineMock;

use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;

class MockEventHandler
{
  function __construct()
  {
  }

  static function make()
  {
    return new self();
  }

  public function formatEvent(Event $event, bool $withQuestions = false)
  {
    return [
      ...$event->only(
        'id',
        'code',
        'title',
        'description',
        'starts_at',
        'num_of_subjects',
        'status'
      ),
      'event_courses' => $event
        ->eventCourseables()
        ->get()
        ->map(function (EventCourseable $eventCourseable) use ($withQuestions) {
          $eventCourseable->course_session = $eventCourseable->courseable->load(
            'course'
          );
          /** @var \App\Models\CourseSession $courseable */
          $courseable = $eventCourseable->courseable;
          $course = $courseable->course;
          return [
            ...$eventCourseable->only('id', 'event_id'),
            'course_session_id' => $eventCourseable->courseable_id,
            'course_session' => [
              ...$courseable->only(
                'id',
                'session',
                'course_id',
                'category',
                'general_instructions'
              ),
              'course' => [
                ...$course->only('id'),
                'course_code' => $course->code,
                'course_title' => $course->title
              ],
              ...$withQuestions
                ? [
                  'questions' => $courseable->questions
                    ->map(
                      fn(Question $question) => [
                        ...$question->only(
                          'id',
                          'question',
                          'question_no',
                          'option_a',
                          'option_b',
                          'option_c',
                          'option_d',
                          'option_e',
                          'answer',
                          'answer_meta'
                        ),
                        'course_session_id' => $question->courseable_id
                      ]
                    )
                    ->toArray(),
                  'passages' => $courseable->passages
                    ->map(
                      fn(Passage $passage) => [
                        ...$passage->only('id', 'passage', 'from', 'to'),
                        'course_session_id' => $passage->courseable_id
                      ]
                    )
                    ->toArray(),
                  'instructions' => $courseable->instructions
                    ->map(
                      fn(Instruction $instruction) => [
                        ...$instruction->only(
                          'id',
                          'instruction',
                          'from',
                          'to'
                        ),
                        'course_session_id' => $instruction->courseable_id
                      ]
                    )
                    ->toArray()
                ]
                : []
            ]
          ];
        })
        ->toArray()
    ];
  }
}
