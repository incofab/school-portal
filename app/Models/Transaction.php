<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'institution_group_id' => 'integer',
    'type' => TransactionType::class,
    'meta' => 'array'
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  // Morph to Funding
  public function transactionable()
  {
    return $this->morphTo();
  }
}
