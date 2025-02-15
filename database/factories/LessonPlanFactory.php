<?php

namespace Database\Factories;

use App\Models\CourseTeacher;
use App\Models\LessonPlan;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\SchemeOfWork;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonPlanFactory extends Factory
{
    protected $model = LessonPlan::class;

    public function definition()
    {
        return [
            'institution_id' => Institution::factory(),
            'scheme_of_work_id' => SchemeOfWork::factory(),
            'course_teacher_id' => CourseTeacher::factory(),
            'objective' => $this->faker->text(200),
            'activities' => $this->faker->text(200),
            'content' => $this->faker->text(500),
        ];
    }

    function schemeOfWork(SchemeOfWork $schemeOfWork)
    {
        return $this->state(fn($attr) => [
            'scheme_of_work_id' => $schemeOfWork->id,
            'institution_id' => $schemeOfWork->institution_id,
        ]);
    }
}
