<?php

use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
  public function definition(): array
  {
    $couseIDs = \App\Models\Course::all('id')
      ->pluck('id')
      ->toArray();

    return [
      'course_id' => $this->faker->randomElement($couseIDs),
      'title' => $this->faker->words(8, true),
      'description' => $this->faker->paragraph
    ];
  }
}
