<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacancyPost extends BaseModel
{
  use HasFactory, InstitutionScope, SoftDeletes;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'is_published' => 'boolean',
    'positions_available' => 'integer',
    'application_deadline' => 'date'
  ];

  public static function createRule(): array
  {
    return [
      'title' => ['required', 'string', 'max:255'],
      'department' => ['nullable', 'string', 'max:255'],
      'employment_type' => ['required', 'string', 'max:80'],
      'location' => ['nullable', 'string', 'max:255'],
      'summary' => ['nullable', 'string'],
      'description' => ['required', 'string'],
      'requirements' => ['nullable', 'string'],
      'responsibilities' => ['nullable', 'string'],
      'salary_range' => ['nullable', 'string', 'max:255'],
      'positions_available' => ['required', 'integer', 'min:1'],
      'application_deadline' => ['nullable', 'date'],
      'is_published' => ['sometimes', 'boolean']
    ];
  }

  public function scopeIsPublished($query, bool $isPublished = true)
  {
    return $query->where('is_published', $isPublished);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function recruitmentApplications()
  {
    return $this->hasMany(RecruitmentApplication::class);
  }
}
