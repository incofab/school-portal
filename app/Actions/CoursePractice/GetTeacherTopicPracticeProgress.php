<?php

namespace App\Actions\CoursePractice;

use App\Models\Classification;
use App\Models\Student;
use App\Models\Topic;
use App\Models\TopicPracticeSummary;

class GetTeacherTopicPracticeProgress
{
  public function __construct(
    private ?int $topicId = null,
    private ?int $classificationId = null
  ) {
  }

  public static function run(
    ?int $topicId = null,
    ?int $classificationId = null
  ): array {
    return (new self($topicId, $classificationId))->execute();
  }

  public function execute(): array
  {
    $topics = Topic::query()
      ->with('course', 'classificationGroup')
      ->withCount([
        'practiceSummaries as attempted_students_count' => fn(
          $query
        ) => $query->where('attempts_count', '>', 0)
      ])
      ->oldest('title')
      ->get();

    $selectedTopic = Topic::query()
      ->with('course', 'classificationGroup')
      ->find($this->topicId ?: $topics->first()?->id);

    $classifications = Classification::query()
      ->when(
        $selectedTopic?->classification_group_id,
        fn($query, $classificationGroupId) => $query->where(
          'classification_group_id',
          $classificationGroupId
        )
      )
      ->oldest('title')
      ->get();

    $selectedClassificationId =
      $this->classificationId ?: $classifications->first()?->id;

    return [
      'topics' => $topics,
      'selectedTopic' => $selectedTopic,
      'classifications' => $classifications,
      'selectedClassificationId' => $selectedClassificationId,
      'students' => $this->getStudents(
        $selectedTopic,
        $selectedClassificationId
      )
    ];
  }

  private function getStudents(?Topic $topic, ?int $classificationId)
  {
    if (!$topic || !$classificationId) {
      return collect();
    }

    $summaries = TopicPracticeSummary::query()
      ->where('topic_id', $topic->id)
      ->where('classification_id', $classificationId)
      ->with([
        'attempts' => fn($query) => $query->latest('id')->limit(5)
      ])
      ->get()
      ->keyBy('student_id');

    return Student::query()
      ->with('user', 'classification')
      ->where('classification_id', $classificationId)
      ->oldest('code')
      ->get()
      ->map(function (Student $student) use ($summaries) {
        $student->setRelation(
          'practice_summary',
          $summaries->get($student->id)
        );

        return $student;
      });
  }
}
