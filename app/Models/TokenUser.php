<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenUser extends Model
{
  use HasFactory, InstitutionScope;

  const TOKEN_COOKIE_NAME = 'exam_token';
  const TOKEN_USER_ID = 'token_user_id';

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'user_id' => 'integer',
    'meta' => AsArrayObject::class
  ];

  function exams()
  {
    return $this->morphMany(Exam::class, 'examable');
  }
}
