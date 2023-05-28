<?php

namespace App\Models;

use App\Enums\InstitutionUserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionUser extends Model
{
  use HasFactory;
  protected $guarded = [];
  public $table = 'institution_users';

  protected $casts = [
    'role' => InstitutionUserType::class
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
