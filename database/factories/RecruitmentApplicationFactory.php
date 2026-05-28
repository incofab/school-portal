<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\RecruitmentApplication;
use App\Models\VacancyPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class RecruitmentApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'vacancy_post_id' => VacancyPost::factory(),
            'application_no' => RecruitmentApplication::generateApplicationNo(),
            'reference' => Str::orderedUuid()->toString(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'other_names' => fake()->optional()->firstName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'current_role' => fake()->jobTitle(),
            'years_of_experience' => fake()->numberBetween(0, 20),
            'highest_qualification' => fake()->randomElement(['NCE', 'B.Ed', 'B.Sc', 'M.Ed', 'M.Sc']),
            'cv_url' => fake()->url(),
            'cover_letter' => fake()->paragraphs(2, true),
            'cover_letter_url' => fake()->optional()->url(),
            'portfolio_url' => fake()->optional()->url(),
            'available_from' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }

    public function vacancyPost(VacancyPost $vacancyPost): static
    {
        return $this->state(
            fn () => [
                'institution_id' => $vacancyPost->institution_id,
                'vacancy_post_id' => $vacancyPost->id,
            ]
        );
    }
}
