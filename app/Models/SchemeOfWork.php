<?php

namespace App\Models;

use App\Enums\TermType;
use App\Rules\ValidateExistsRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchemeOfWork extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'scheme_of_works';
    protected $guarded = [];

    protected $casts = [
        'term' => TermType::class,
        'institution_group_id' => 'integer',
        'institution_id' => 'integer',
        'topic_id' => 'integer',
        'week_number' => 'integer',
    ];

    static function createRule()
    {
        return [
            'term' => ['required'],
            'topic_id' => ['required', new ValidateExistsRule(Topic::class)],
            'week_number' => ['required', 'integer'],
            'learning_objectives' => ['nullable', 'string'],
            'resources' => ['nullable', 'string'],
            'is_used_by_institution_group' => ['required', 'boolean'],
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

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class);
    }
}
