<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSubject extends Model
{
  use HasFactory;

  protected $fillable = [
    'event_id',
    'course_id',
    'course_session_id',
    'status'
  ];

  function insert($post)
  {
    $data = static::create($post);

    if (!$data) {
      return retF('Error: Data entry failed');
    }

    return retS('Data recorded', $data);
  }

  static function multiSubjectInsert($post)
  {
    $sessionIDs = $post['course_session_id'];

    foreach ($sessionIDs as $sessionId) {
      $acadSession = CourseSession::where('id', '=', $sessionId)->first();

      if (!$acadSession) {
        return retF('An invalid Academic Session was supplied');
      }

      // No 2 courses (even if with different years should be in the save event)
      //Check if this course code already exist in this event
      if (
        EventSubject::whereEvent_id($post['event_id'])
          ->whereCourse_id($acadSession['course_id'])
          ->first()
      ) {
        return retF('A subject cannot appear multiple times');
      }

      $arr = [
        'event_id' => $post['event_id'],
        'course_id' => $acadSession['course_id'],
        'course_session_id' => $sessionId,
        'status' => 'active'
      ];

      static::create($arr);
    }

    return retS('Data recorded');
  }

  function event()
  {
    return $this->belongsTo(\App\Models\Event::class, 'event_id', 'id');
  }

  function course()
  {
    return $this->belongsTo(\App\Models\Course::class, 'course_id', 'id');
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
