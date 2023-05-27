<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passage extends Model
{
  use HasFactory;
  protected $fillable = ['course_session_id', 'passage', 'from_', 'to_'];

  function session()
  {
    return $this->belongsTo(
      \App\Models\CourseSession::class,
      'course_session_id',
      'id'
    );
  }
}
