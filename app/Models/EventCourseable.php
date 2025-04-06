<?php

namespace App\Models;

use App\Models\Support\QuestionCourseable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventCourseable extends QuestionCourseable
{
  use HasFactory;

  protected $guarded = [];

  function event()
  {
    return $this->belongsTo(Event::class);
  }

  // CourseSession | Course
  function courseable()
  {
    return $this->morphTo('courseable');
  }

  function getName()
  {
    return $this->event->title;
  }
}
