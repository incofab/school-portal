<?php

namespace Database\Factories;

use App\Enums\NoteStatusType;
use App\Enums\TermType;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LessonNote>
 */
class LessonNoteFactory extends Factory
{
    protected $model = LessonNote::class;

    public function definition()
    {
        return [
            'institution_id' => Institution::factory(),
            'classification_group_id' => ClassificationGroup::factory(),
            'classification_id' => Classification::factory(),
            'lesson_plan_id' => LessonPlan::factory(),
            'course_id' => Course::factory(),
            'topic_id' => Topic::factory(),
            'course_teacher_id' => CourseTeacher::factory(),
            'term' => TermType::First->value,
            'title' => $this->faker->text(100),
            'content' => $this->faker->text(500),
            'status' => NoteStatusType::Published->value,
        ];
    }

    function lessonPlan(LessonPlan $lessonPlan,
    Classification $classification = null)
    {
        return $this->state(fn($attr) => [
            'lesson_plan_id' => $lessonPlan->id,
            'institution_id' => $lessonPlan->institution_id,
            'classification_id' => $classification ?? Classification::factory(),
        ]);
    }

    function courseTeacher(CourseTeacher $courseTeacher)
    {
        return $this->state(fn($attr) => [
            'course_teacher_id' => $courseTeacher->id,
        ]);
    }
}
