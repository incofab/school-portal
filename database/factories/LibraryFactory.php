<?php

namespace Database\Factories;

use App\Enums\LibrarySourceType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Library;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Library>
 */
class LibraryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'institution_user_id' => InstitutionUser::factory(),
            'academic_session_id' => AcademicSession::factory(),
            'course_id' => null,
            'term' => TermType::First->value,
            'title' => fake()->sentence(4),
            'material_type' => 'document',
            'source_type' => LibrarySourceType::External->value,
            'description' => fake()->paragraph(),
            'is_public' => true,
            'is_published' => true,
            'external_url' => fake()->url(),
            'published_at' => now(),
        ];
    }

    public function withClassifications($classifications): static
    {
        return $this->afterCreating(function (Library $library) use (
            $classifications
        ) {
            $library->classifications()->attach(
                $classifications
                    ->mapWithKeys(
                        fn ($classification) => [
                            $classification->id => [
                                'institution_id' => $library->institution_id,
                            ],
                        ]
                    )
                    ->toArray()
            );

            $library->forceFill(['is_public' => false])->save();
        });
    }
}
