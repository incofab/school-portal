<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionGroup extends Model
{
  use HasFactory;

  public $guarded = [];
  public $casts = ['partner_user_id' => 'integer', 'user_id' => 'integer'];

  static function getQueryForManager(User $user)
  {
    return $user->isAdmin()
      ? InstitutionGroup::query()
      : $user->partnerInstitutionGroups();
  }

  function institutions()
  {
    return $this->hasMany(Institution::class);
  }
  function user()
  {
    return $this->belongsTo(User::class);
  }
  function partner()
  {
    return $this->belongsTo(User::class, 'partner_user_id');
  }
}
