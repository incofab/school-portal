<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Topic extends Model
{
  use HasFactory;

  protected $fillable = ['course_id', 'title', 'description'];

  static function ruleCreate()
  {
    return [
      'course_id' => ['required'],
      'title' => ['required']
    ];
  }

  static function ruleUpdate()
  {
    return [];
  }

  function insert($post)
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

    if (
      $this->where('course_id', '=', $post['course_id'])
        ->where('title', '=', $post['title'])
        ->first()
    ) {
      return [
        SUCCESSFUL => false,
        MESSAGE => "'{$post['title']}' already exist for this course ID = {$post['course_id']}"
      ];
    }

    $ret = $this->create($post);

    if ($ret) {
      return [
        SUCCESSFUL => true,
        MESSAGE => 'Data recorded successfully',
        'data' => $ret->toArray()
      ];
    }

    return [SUCCESSFUL => false, MESSAGE => 'Data entry failed'];
  }

  function course()
  {
    return $this->belongsTo(\App\Models\Course::class, 'course_id', 'id');
  }
}
