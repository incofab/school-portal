<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
  use HasFactory, InstitutionScope;

  protected $casts = [
    'institution_id' => 'integer'
  ];
  protected $fillable = [
    'code',
    'category',
    'title',
    'description',
    'is_file_content_uploaded',
    'institution_id'
  ];

  public function canDelete()
  {
    return $this->sessions()
      ->get()
      ->count() === 0 &&
      $this->topics()
      ->get()
      ->count() === 0 &&
      $this->summaryChapters()
      ->get()
      ->count() === 0;
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function sessions()
  {
    return $this->hasMany(CourseSession::class);
  }

  function topics()
  {
    return $this->hasMany(Topic::class);
  }

  function summaryChapters()
  {
    return $this->hasMany(Summary::class);
  }

  function courseTeachers()
  {
    return $this->hasMany(CourseTeacher::class);
  }

  public function noteTopics()
  {
    return $this->hasMany(NoteTopic::class);
  }
}