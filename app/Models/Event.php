<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
  use HasFactory;

  protected $fillable = [
    'institution_id',
    'title',
    'description',
    'duration',
    'num_of_activations',
    'status'
  ];

  static function insert($post)
  {
    if (
      $data = Event::where('institution_id', '=', $post['institution_id'])
        ->where('title', '=', $post['title'])
        ->first()
    ) {
      return retS('Title already Exists', $data);
    }

    $data = static::create($post);

    if ($data) {
      return retS('Data recorded', $data);
    }

    return retF('Error: Data entry failed');
  }

  static function edit($post)
  {
    $event = Event::where('id', '=', $post['id'])->firstOrFail();
    //Fillable needs to be called
    $event->update($post);

    return retS('Data recorded', $event);
  }

  static function getActiveEvents($institutionId)
  {
    return static::where('status', 'active')
      ->where('institution_id', $institutionId)
      ->with('eventSubjects')
      ->get();
  }

  function institution()
  {
    return $this->belongsTo(
      \App\Models\Institution::class,
      'institution_id',
      'id'
    );
  }

  function eventSubjects()
  {
    return $this->hasMany(\App\Models\EventSubject::class, 'event_id', 'id');
  }
}
