<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSession extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  static function createRule($editUser = null)
  {
    return [
      'session' => ['required', 'string'],
      'category' => ['nullable', 'string'],
      'general_instructions' => ['nullable', 'string']
    ];
  }

  function course()
  {
    return $this->belongsTo(Course::class);
  }

  function questions()
  {
    return $this->morphMany(Question::class, 'courseable');
  }

  function instructions()
  {
    return $this->morphMany(Instruction::class, 'courseable');
  }

  function passages()
  {
    return $this->morphMany(Passage::class, 'courseable');
  }
}
