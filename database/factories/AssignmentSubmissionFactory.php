<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssignmentSubmission>
 */
class AssignmentSubmissionFactory extends Factory
{
    protected $model = AssignmentSubmission::class;

    public function definition()
    {
        return [
            'assignment_id' => Assignment::factory(),
            'student_id' => Student::factory(),
            'answer' => $this->faker->text(200),
            'attachments' => null,
            'score' => $this->faker->numberBetween(0, 20),
        ];
    }
}