<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
  use HasFactory;

  protected $guarded = [];

  static function ruleCreate()
  {
    return [
      'course_id' => ['required'],
      'title' => ['required']
    ];
  }

  function course()
  {
    return $this->belongsTo(Course::class);
  }
}
