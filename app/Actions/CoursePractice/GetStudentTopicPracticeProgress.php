<?php

namespace App\Actions\CoursePractice;

use App\Enums\NoteStatusType;
use App\Models\Course;
use App\Models\Student;
use App\Models\Topic;
use App\Models\TopicPracticeAttempt;
use App\Models\TopicPracticeSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class GetStudentTopicPracticeProgress
{
  public function __construct(private Student $student)
  {
  }

  public static function run(Student $student): array
  {
    return (new self($student))->execute();
  }

  public function execute(): array
  {
    $this->student->loadMissing('classification.classificationGroup');
    $classificationGroupId =
      $this->student->classification?->classification_group_id;

    $summaries = TopicPracticeSummary::query()
      ->where('student_id', $this->student->id)
      ->get()
      ->keyBy('topic_id');

    $courses = Course::query()
      ->whereHas(
        'topics',
        fn(Builder $query) => $this->applyPracticeableTopicScope(
          $query,
          $classificationGroupId
        )
      )
      ->with([
        'topics' => fn($query) => $this->applyPracticeableTopicScope(
          $query,
          $classificationGroupId
        )->oldest('title')
      ])
      ->orderedByCourseOrder()
      ->get()
      ->map(function (Course $course) use ($summaries) {
        $course->topics->each(function (Topic $topic) use ($summaries) {
          $topic->setRelation('practice_summary', $summaries->get($topic->id));
        });

        return $course;
      });

    return [
      'courses' => $courses,
      'attempts' => TopicPracticeAttempt::query()
        ->where('student_id', $this->student->id)
        ->with('course', 'topic')
        ->latest('id')
        ->limit(30)
        ->get()
    ];
  }

  private function applyPracticeableTopicScope(
    Builder|Relation $query,
    ?int $classificationGroupId
  ): Builder|Relation {
    return $query
      ->where('classification_group_id', $classificationGroupId)
      ->whereHas(
        'lessonNotes',
        fn(Builder $lessonNoteQuery) => $lessonNoteQuery->where(
          'status',
          NoteStatusType::Published->value
        )
      );
  }
}
