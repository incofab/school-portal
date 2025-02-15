<?php

namespace App\Models;

use App\Enums\NoteStatusType;
use App\Rules\ValidateExistsRule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonNote extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'lesson_notes';
    protected $guarded = [];

    protected $casts = [
        'institution_group_id' => 'integer',
        'institution_id' => 'integer',
        'classification_group_id' => 'integer',
        'classification_id' => 'integer',
        'lesson_plan_id' => 'integer',
        'course_id' => 'integer',
        'topic_id' => 'integer',
        'course_teacher_id' => 'integer',
        'status' => NoteStatusType::class,
    ];

    static function createRule()
    {
        return [
            // 'classification_group_id' => ['nullable', new ValidateExistsRule(ClassificationGroup::class)],
            // 'classification_id' => ['required', new ValidateExistsRule(Classification::class)],
            'lesson_plan_id' => ['nullable', new ValidateExistsRule(LessonPlan::class)],
            // 'course_id' => ['required', new ValidateExistsRule(Course::class)],
            // 'course_teacher_id' => ['required', new ValidateExistsRule(CourseTeacher::class)],
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
            // 'status' => ['nullable', 'string'],
            'is_published' => ['required', 'boolean'],
            'is_used_by_institution_group' => ['required', 'boolean'],
            'is_used_by_classification_group' => ['required', 'boolean'],
            'institution_id' => ['required'],
        ];
    }

    public function institutionGroup()
    {
        return $this->belongsTo(InstitutionGroup::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function classificationGroup()
    {
        return $this->belongsTo(ClassificationGroup::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseTeacher()
    {
        return $this->belongsTo(CourseTeacher::class);
    }
}
