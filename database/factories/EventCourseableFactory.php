<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventCourseableFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'event_id' => Event::factory(),
      'courseable_type' => (new CourseSession())->getMorphClass(),
      'courseable_id' => CourseSession::factory()
    ];
  }
}
