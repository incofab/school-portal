<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Question extends Model
{
  use HasFactory;

  protected $fillable = [
    'course_session_id',
    'question_no',
    'question', //'topic_id',
    'option_a',
    'option_b',
    'option_c',
    'option_d',
    'option_e',
    'answer',
    'answer_meta'
  ];

  static function ruleCreate()
  {
    return [
      'course_session_id' => ['required', 'numeric'],
      'question_no' => ['required', 'numeric'],
      'question' => ['required', 'string'],
      'option_a' => ['required', 'string'],
      'option_b' => ['required', 'string'],
      'option_c' => ['required', 'string'],
      'answer' => ['required', 'string']
    ];
  }

  static function ruleUpdate()
  {
    return [];
  }

  static function insert($post)
  {
    if (
      Question::whereCourse_session_id($post['course_session_id'])
        ->whereQuestion_no('question_no')
        ->first()
    ) {
      return [
        SUCCESSFUL => false,
        MESSAGE => 'Duplicate Question ',
        'is_duplicate' => true
      ];
    }

    $created = static::create($post);

    if ($created) {
      return [
        SUCCESSFUL => true,
        MESSAGE => 'Data recorded successfully',
        'data' => $created
      ];
    }

    return [SUCCESSFUL => false, MESSAGE => 'Error: Data entry failed'];
  }

  static function edit($post)
  {
    /** @var $val \Illuminate\Contracts\Validation\Validator|\Illuminate\Contracts\Validation\Factory */
    $val = \Illuminate\Support\Facades\Validator::make(
      $post,
      self::ruleUpdate()
    );

    if ($val->fails()) {
      return [
        SUCCESSFUL => false,
        MESSAGE =>
          'Validation failed: ' . getFirstValue($val->errors()->toArray()),
        'val' => $val
      ];
    }

    //Check if row exists
    if (!static::where('id', '=', $post['id'])->first()) {
      return [
        SUCCESSFUL => false,
        MESSAGE => 'There is no existing record, create new one'
      ];
    }

    $success = static::where('id', '=', $post['id'])->update($post);

    if ($success) {
      return [SUCCESSFUL => true, MESSAGE => 'Record updated successfully'];
    }

    return [SUCCESSFUL => false, MESSAGE => 'Error: Update failed'];
  }

  static function getNumOfQuestions($sessionId)
  {
    $sql =
      'SELECT COUNT(id) AS count_query FROM questions WHERE course_session_id = :session_id';

    $arr = [
      ':session_id' => $sessionId
    ];

    $superArray = BaseModel::pdoQuery($sql, $arr);

    return Arr::get($superArray, 'count_query', 0);
  }

  function topic()
  {
    return $this->belongsTo(\App\Models\Topic::class, 'topic_id', 'id');
  }

  function session()
  {
    return $this->belongsTo(
      \App\Models\CourseSession::class,
      'course_session_id',
      'id'
    );
  }
}
