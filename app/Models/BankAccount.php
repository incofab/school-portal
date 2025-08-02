<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  protected $casts = [
    'is_primary' => 'boolean',
    'institution_id' => 'integer',
    'accountable_id' => 'integer'
  ];

  // Partner | InstitutionGroup
  public function accountable()
  {
    return $this->morphTo('accountable');
  }

  public function withdrawals()
  {
    return $this->hasMany(Withdrawal::class);
  }
}
