<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funding extends Model
{
  use HasFactory;

  protected $table = 'fundings';
  protected $guarded = [];

  protected $casts = [
    'institution_group_id' => 'integer',
    'amount' => 'float',
    'previous_balance' => 'float',
    'new_balance' => 'float',
    'created_at' => 'datetime',
    'updated_at' => 'datetime'
  ];

  static function debtReference($reference)
  {
    return "$reference-debt";
  }

  static function creditReference($reference)
  {
    return "$reference-credit";
  }

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  // Can be PaymentReference
  public function fundable()
  {
    return $this->morphTo();
  }

  function transactions()
  {
    return $this->morphMany(Transaction::class, 'transactionable');
  }
}
