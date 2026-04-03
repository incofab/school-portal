<?php

namespace App\Models;

use App\Enums\WalletType;
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
    'updated_at' => 'datetime',
    'wallet' => WalletType::class
  ];

  static function debtReference($reference)
  {
    return "$reference-debt";
  }

  static function creditReference($reference)
  {
    return "$reference-credit";
  }

  function revertReference()
  {
    return "revert-{$this->reference}";
  }

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  /**
   * The entity creating the funding
   * Can be PaymentReference
   */
  public function fundable()
  {
    return $this->morphTo();
  }

  function transaction()
  {
    return $this->morphOne(Transaction::class, 'transactionable');
  }
}
