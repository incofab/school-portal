<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commission extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'institution_group_id' => 'integer',
    'partner_id' => 'integer',
    'commissionable_id' => 'integer',
    'amount' => 'float'
  ];

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  public function partner()
  {
    return $this->belongsTo(Partner::class);
  }

  // Transaction | null
  function commissionable()
  {
    return $this->morphTo('commissionable');
  }
}
