<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'institution_group_id' => 'integer',
    'type' => TransactionType::class,
    'meta' => 'array'
  ];

  static function record(
    $instGroup,
    $reference,
    WalletType $wallet,
    $amount,
    TransactionType $type,
    $bbt,
    $bat,
    $transactionable = null
  ) {
    self::query()->firstOrCreate(
      ['reference' => $reference],
      [
        'institution_group_id' => $instGroup->id,
        'wallet' => $wallet,
        'amount' => $amount,
        'type' => $type,
        'bbt' => $bbt,
        'bat' => $bat,
        'transactionable_type' => $transactionable?->getMorphClass(),
        'transactionable_id' => $transactionable?->id
      ]
    );
  }

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
