<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class TheoryQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'course_session_id' => CourseSession::factory(),
            'question_number' => $this->faker->unique()->numberBetween(1, 10000),
            'question_sub_number' => $this->faker->optional()->randomElement(['a', 'b', 'c']),
            'question' => $this->faker->paragraph,
            'marks' => $this->faker->randomFloat(1, 1, 20),
            'answer' => $this->faker->paragraph,
            'marking_scheme' => $this->faker->paragraph,
        ];
    }

    public function courseSession(CourseSession $courseSession): static
    {
        return $this->state(
            fn (array $attributes) => [
                'institution_id' => $courseSession->institution_id,
                'course_session_id' => $courseSession->id,
            ]
        );
    }
}
