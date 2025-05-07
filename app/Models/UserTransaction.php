<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserTransaction extends Model
{
  protected $table = 'user_transactions';

  protected $guarded = [];

  protected $casts = [
    'amount' => 'decimal:2',
    'bbt' => 'decimal:2',
    'bat' => 'decimal:2',
    'meta' => 'array'
  ];

  // Partner
  public function entity(): MorphTo
  {
    return $this->morphTo();
  }

  //
  public function trxable(): MorphTo
  {
    return $this->morphTo();
  }
}
