<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
  use HasFactory;

  protected $fillable = ['course_session_id', 'instruction', 'from', 'to'];

  function session()
  {
    return $this->belongsTo(
      \App\Models\CourseSession::class,
      'course_session_id',
      'id'
    );
  }
}
