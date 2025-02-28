<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTeacher extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'classification_id' => 'integer',
    'user_id' => 'integer',
    'course_id' => 'integer'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }
}
