<?php

namespace App\Models;

use App\Enums\UserRoleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionUser extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'role' => UserRoleType::class
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
