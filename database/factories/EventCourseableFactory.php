<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Event;
use App\Models\Institution;
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

  function institution(Institution $institution): static
  {
    return $this->state(
      fn($attr) => ['event_id' => Event::factory()->institution($institution)]
    );
  }

  public function event(Event $event): static
  {
    return $this->state(
      fn(array $attributes) => [
        'event_id' => $event->id,
        'courseable_id' => CourseSession::factory()->institution(
          $event->institution
        )
      ]
    );
  }
}
