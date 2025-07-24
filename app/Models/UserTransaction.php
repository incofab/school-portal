<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserTransaction extends Model
{
  protected $table = 'user_transactions';

  protected $guarded = [];

  protected $casts = [
    'amount' => 'float',
    'bbt' => 'float',
    'bat' => 'float',
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
