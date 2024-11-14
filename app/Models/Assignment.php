<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\TermType;
use App\Rules\ValidateExistsRule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';

    protected $guarded = [];
    protected $casts = [
        'max_score' => 'integer',
        'expires_at' => 'datetime',
        'status' => AssignmentStatus::class,
        'course_teacher_id' => 'integer',
        'classification_id' => 'integer',
        'course_id' => 'integer',
        'academic_session_id' => 'integer',
        'term' => TermType::class,
    ];

    static function createRule()
    {
        return [
            'course_teacher_id' => ['required', new ValidateExistsRule(CourseTeacher::class)],
            'max_score' => ['required', 'integer', 'min:1'],
            'content' => ['required', 'string'],
            'expires_at' => ['required', 'date', 'after:now']
        ];
    }

    // Define relationships
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function courseTeacher()
    {
        return $this->belongsTo(CourseTeacher::class);
    }

    function assignmentSubmissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}