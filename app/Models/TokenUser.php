<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenUser extends Model
{
  use HasFactory;

  const TOKEN_COOKIE_NAME = 'exam_token';
  const TOKEN_USER_ID = 'token_user_id';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  function exams()
  {
    return $this->morphMany(Exam::class, 'examable');
  }
}
