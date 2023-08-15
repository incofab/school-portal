<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
  use HasFactory;

  protected $fillable = [
    'course_id',
    'chapter_no',
    'title',
    'description',
    'summary'
  ];

  static function ruleCreate()
  {
    return [
      'course_id' => ['required', 'numeric'],
      'chapter_no' => ['required', 'string'],
      'title' => ['required', 'string'],
      'summary' => ['required', 'string']
    ];
  }

  function course()
  {
    return $this->belongsTo(Course::class);
  }
}
