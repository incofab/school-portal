<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Institution;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class PassageFactory extends Factory
{
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'passage' => fake()->paragraph(),
      'from' => fake()->randomNumber(1),
      'to' => fake()->randomNumber(2),
      'courseable_type' => MorphMap::key(CourseSession::class),
      'courseable_id' => CourseSession::factory()
    ];
  }

  public function courseable(Model $courseable): static
  {
    return $this->state(
      fn(array $attributes) => [
        'courseable_type' => $courseable->getMorphClass(),
        'courseable_id' => $courseable->id
      ]
    );
  }
}
