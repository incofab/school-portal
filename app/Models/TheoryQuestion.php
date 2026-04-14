<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TheoryQuestion extends Model
{
    use HasFactory, InstitutionScope;

    protected $guarded = [];

    protected $casts = [
        'institution_id' => 'integer',
        'course_session_id' => 'integer',
        'question_number' => 'integer',
        'marks' => 'float',
    ];

    public static function createRule(?TheoryQuestion $theoryQuestion = null): array
    {
        return [
            'question_number' => ['required', 'integer', 'min:1'],
            'question_sub_number' => ['nullable', 'string', 'max:20'],
            'question' => ['required', 'string'],
            'marks' => ['required', 'numeric', 'min:0'],
            'answer' => ['required', 'string'],
            'marking_scheme' => ['nullable', 'string'],
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function courseSession()
    {
        return $this->belongsTo(CourseSession::class);
    }
}
