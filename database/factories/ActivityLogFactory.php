<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'action' => 'created',
            'category' => 'system',
            'event' => 'audit.test_event',
            'description' => fake()->sentence(),
            'severity' => 'info',
            'properties' => [],
        ];
    }

    public function forInstitution(Institution $institution): static
    {
        return $this->state(
            fn () => [
                'institution_id' => $institution->id,
                'institution_group_id' => $institution->institution_group_id,
            ]
        );
    }
}
