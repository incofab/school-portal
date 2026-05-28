<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacancyPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Academics', 'Administration', 'Student Affairs']),
            'employment_type' => fake()->randomElement(['full-time', 'part-time', 'contract']),
            'location' => fake()->city(),
            'summary' => fake()->paragraph(),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraphs(2, true),
            'responsibilities' => fake()->paragraphs(2, true),
            'salary_range' => fake()->randomElement(['Competitive', 'Negotiable']),
            'positions_available' => fake()->numberBetween(1, 5),
            'application_deadline' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'is_published' => true,
        ];
    }
}
