<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\SchemeOfWork;
use App\Models\Institution;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchemeOfWorkFactory extends Factory
{
    protected $model = SchemeOfWork::class;

    public function definition()
    {
        return [
            'institution_id' => Institution::factory(),
            'term' => TermType::First->value,
            'topic_id' => Topic::factory(),
            'week_number' => $this->faker->numberBetween(1, 13),
            'learning_objectives' => $this->faker->paragraph,
            'resources' => $this->faker->sentence,
            // 'is_used_by_institution_group' => $this->faker->randomElement([true, false]),
        ];
    }

    function topic(Topic $topic)
    {
        return $this->state(fn($attr) => [
            'topic_id' => $topic->id,
            'institution_id' => $topic->institution_id,
        ]);
    }
}
