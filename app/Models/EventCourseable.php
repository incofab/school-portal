<?php

namespace App\Models;

use App\Models\Support\QuestionCourseable;
use App\Rules\ValidateMorphRule;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;

class EventCourseable extends QuestionCourseable
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'event_id' => 'integer',
    'courseable_id' => 'integer'
  ];

  static function createRule($prefix = '')
  {
    return [
      $prefix . 'courseable_id' => ['required', 'integer'],
      $prefix . 'courseable_type' => [
        'required',
        new ValidateMorphRule('courseable'),
        Rule::in(MorphMap::keys([CourseSession::class]))
      ]
    ];
  }

  function event()
  {
    return $this->belongsTo(Event::class);
  }

  // CourseSession
  function courseable()
  {
    return $this->morphTo('courseable');
  }

  function getName()
  {
    return $this->event->title;
  }
}
