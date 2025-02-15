<?php

namespace Database\Factories;

use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'course_id' => Course::factory(),
      'title' => $this->faker->words(8, true),
      'description' => $this->faker->paragraph
    ];
  }
  function parentTopic(Topic $topic)
  {
    return $this->state(fn($attr) => [
      'parent_topic_id' => $topic,
    ]);
  }
  function course(Course $course)
  {
    return $this->state(fn($attr) => [
      'course_id' => $course->id,
      'institution_id' => $course->institution_id,
    ]);
  }

  public function classificationGroup(
    ClassificationGroup $classificationGroup
  ): static {
    return $this->state(
      fn(array $attributes) => [
        'classification_group_id' => $classificationGroup->id,
        'institution_id' => $classificationGroup->institution_id,
      ]
    );
  }
}
