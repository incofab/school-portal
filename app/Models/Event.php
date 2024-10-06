<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'status' => EventStatus::class,
    'starts_at' => 'datetime'
  ];

  public function duration(): Attribute
  {
    return Attribute::make(
      get: fn($value) => $value ? floor($value / 60) : null,
      set: fn($value) => $value ? $value * 60 : null
    );
  }
  function getDurationInSeconds()
  {
    return $this->getRawOriginal('duration');
  }
  static function scopeActive($query, $status = 'active')
  {
    return $query->where('status', $status);
  }

  function canCreateExamCheck()
  {
    if ($this->status !== EventStatus::Active) {
      return [false, 'Event is not active'];
    }
    if ($this->starts_at->greaterThan(now())) {
      return [false, "It's not yet time"];
    }

    if ($this->eventCourseables()->count() < $this->num_of_subjects) {
      return [
        false,
        'Event is not ready yet. It does not contain enough subjects'
      ];
    }
    return [true, ''];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function exams()
  {
    return $this->hasMany(Exam::class);
  }

  function eventCourseables()
  {
    return $this->hasMany(EventCourseable::class);
  }
}
