<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeePaymentTrack extends Model
{
  use HasFactory, SoftDeletes;

  public $guarded = [];

  function feePayment()
  {
    return $this->belongsTo(FeePayment::class);
  }
  function confirmedBy()
  {
    return $this->belongsTo(User::class, 'confirmed_by_user_id');
  }
}
