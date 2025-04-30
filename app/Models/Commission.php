<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commission extends Model
{
  use HasFactory;

  protected $guarded = [];

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  public function partner()
  {
    return $this->belongsTo(Partner::class);
  }
}
