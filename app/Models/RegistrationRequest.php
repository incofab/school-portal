<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistrationRequest extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];

  protected $casts = [
    'partner_user_id' => 'integer',
    'data' => AsArrayObject::class
  ];

  function scopeNotRegistered($query)
  {
    return $query->whereNull('institution_registered_at');
  }

  function scopeSearch($query, $search)
  {
    return $query->when(
      $search,
      fn($q) => $q->where('reference', 'LIKE', "%$search%")
    );
  }

  function partner()
  {
    return $this->belongsTo(User::class, 'partner_user_id');
  }
}
