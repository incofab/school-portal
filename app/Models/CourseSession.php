<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CourseSession extends Model
{
  use HasFactory;

  public $fillable = [
    'course_id',
    'category',
    'session',
    'general_instructions',
    'file_path',
    'file_version'
  ];

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

    $course = \App\Models\Course::where('id', '=', $post['course_id'])->first();

    if (!$course) {
      return retF('Course detail not found');
    }

    if (
      CourseSession::whereCourse_id($post['course_id'])
        ->whereSession($post['session'])
        ->first()
    ) {
      return retF(
        "{$post['session']} session already exist for this course ID = {$post['course_id']}"
      );
    }
    $created = static::create($post);

    if ($created) {
      return retS('Data recorded successfully');
    }

    return retF('Error: operation failed');
  }

  function savePerQuestionsInstructions($courseId, $sessionID, $post)
  {
    // First delete all the existing ones
    \App\Models\Instruction::where(
      'course_session_id',
      '=',
      $sessionID
    )->delete();

    foreach ($post as $data) {
      if (
        empty($data['instruction']) ||
        empty($data['from_']) ||
        empty($data['to_'])
      ) {
        continue;
      }
      if (!is_numeric($data['from_']) || !is_numeric($data['to_'])) {
        continue;
      }

      \App\Models\Instruction::create([
        'course_id' => $courseId,
        'course_session_id' => $sessionID,
        'instruction' => $data['instruction'],
        'from_' => $data['from_'],
        'to_' => $data['to_']
      ]);
    }
  }

  function savePassages($courseId, $sessionID, $post)
  {
    // First delete all the existing ones
    \App\Models\Passage::where('course_session_id', '=', $sessionID)->delete();

    foreach ($post as $data) {
      if (
        empty($data['passage']) ||
        empty($data['from_']) ||
        empty($data['to_'])
      ) {
        continue;
      }
      if (!is_numeric($data['from_']) || !is_numeric($data['to_'])) {
        continue;
      }

      \App\Models\Passage::create([
        'course_id' => $courseId,
        'course_session_id' => $sessionID,
        'passage' => $data['passage'],
        'from_' => $data['from_'],
        'to_' => $data['to_']
      ]);
    }
  }

  static function joinUpInstruction($post)
  {
    $arr = [];
    if (!isset($post['all_instruction'])) {
      return $arr;
    }

    $len = count(Arr::get($post['all_instruction'], 'instruction', []));

    for ($i = 0; $i < $len; $i++) {
      $arr[] = [
        'instruction' => $post['all_instruction']['instruction'][$i],
        'from_' => $post['all_instruction']['from_'][$i],
        'to_' => $post['all_instruction']['to_'][$i],
        'table_id' => Arr::get(Arr::get($post['all_instruction'], 'id'), $i)
      ];
    }
    return $arr;
  }

  static function joinUpPassage($post)
  {
    $arr = [];
    if (!isset($post['all_passages'])) {
      return $arr;
    }

    $len = count(Arr::get($post['all_passages'], 'passage', []));

    for ($i = 0; $i < $len; $i++) {
      $arr[] = [
        'passage' => $post['all_passages']['passage'][$i],
        'from_' => $post['all_passages']['from_'][$i],
        'to_' => $post['all_passages']['to_'][$i],
        'id' => Arr::get(Arr::get($post['all_passages'], 'id'), $i)
      ];
    }

    return $arr;
  }

  function course()
  {
    return $this->belongsTo(\App\Models\Course::class, 'course_id', 'id');
  }

  function questions()
  {
    return $this->hasMany(
      \App\Models\Question::class,
      'course_session_id',
      'id'
    );
  }

  function instructions()
  {
    return $this->hasMany(
      \App\Models\Instruction::class,
      'course_session_id',
      'id'
    );
  }

  function passages()
  {
    return $this->hasMany(
      \App\Models\Passage::class,
      'course_session_id',
      'id'
    );
  }
}
