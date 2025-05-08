<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\EventCourseable;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class InstructionFactory extends Factory
{
  public function definition(): array
  {
    $from = fake()->randomNumber(1);
    $to = $from + fake()->randomNumber(1) + 1;
    return [
      'institution_id' => Institution::factory(),
      'instruction' => fake()->paragraph(),
      'from' => $from,
      'to' => $to,
      'courseable_type' => MorphMap::key(CourseSession::class),
      'courseable_id' => CourseSession::factory()
    ];
  }

  public function courseable(Model $courseable): static
  {
    $institutionId =
      $courseable instanceof EventCourseable
        ? $courseable->event->institution_id
        : $courseable->institution_id;
    return $this->state(
      fn(array $attributes) => [
        'courseable_type' => $courseable->getMorphClass(),
        'courseable_id' => $courseable->id,
        'institution_id' => $institutionId
      ]
    );
  }
}
