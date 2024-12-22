<?php

namespace App\Models;

use App\Enums\NoteStatusType;
use App\Rules\ValidateExistsRule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoteTopic extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'note_topics';
    protected $guarded = [];

    protected $casts = [
        'institution_group_id' => 'integer',
        'institution_id' => 'integer',
        'course_teacher_id' => 'integer',
        'classification_group_id' => 'integer',
        'classification_id' => 'integer',
        'course_id' => 'integer',
        'status' => NoteStatusType::class,
    ];

    static function createRule()
    {
        return [
            'course_teacher_id' => ['required', new ValidateExistsRule(CourseTeacher::class)],
            'title' => ['required', 'string'],
            'content' => ['required', 'string']
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

    public function courseTeacher()
    {
        return $this->belongsTo(CourseTeacher::class);
    }

    public function classificationGroup()
    {
        return $this->belongsTo(ClassificationGroup::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function noteSubTopics()
    {
        return $this->hasMany(NoteSubTopic::class);
    }
}