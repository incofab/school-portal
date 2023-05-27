<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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

  static function ruleUpdate()
  {
    return [];
  }

  static function insert($post)
  {
    /** @var $val \Illuminate\Contracts\Validation\Validator|\Illuminate\Contracts\Validation\Factory */
    $val = \Illuminate\Support\Facades\Validator::make(
      $post,
      self::ruleCreate()
    );

    if ($val->fails()) {
      return [
        SUCCESSFUL => false,
        MESSAGE =>
          'Validation failed: ' . getFirstValue($val->errors()->toArray()),
        'val' => $val
      ];
    }

    //Check if chapter_no already exists under the same courseName (chapter numbers must be unique
    if (
      static::where('chapter_no', '=', $post['chapter_no'])
        ->where('course_id', '=', $post['course_id'])
        ->first()
    ) {
      return [
        SUCCESSFUL => false,
        MESSAGE => 'This Chapter No already
					exists, Chapter Numbers must be unique'
      ];
    }

    $created = static::create($post);

    if ($created) {
      return [SUCCESSFUL => true, MESSAGE => 'Data recorded successfully'];
    }

    return [SUCCESSFUL => false, MESSAGE => 'Error: Data entry failed'];
  }

  function course()
  {
    return $this->belongsTo(\App\Models\Course::class, 'course_id', 'id');
  }
}
