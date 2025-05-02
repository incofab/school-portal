<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeePayment extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  public $casts = [
    'institution_id' => 'integer',
    'receipt_id' => 'integer',
    'fee_id' => 'integer',
    'confirmed_by_user_id' => 'integer',
    'payable_id' => 'integer'
  ];

  function fee()
  {
    return $this->belongsTo(Fee::class);
  }
  function confirmedBy()
  {
    return $this->belongsTo(User::class, 'confirmed_by_user_id');
  }
  function receipt()
  {
    return $this->belongsTo(Receipt::class);
  }
  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
  /**
   * The entity making the payment eg. User
   */
  function payable()
  {
    return $this->morphTo('payable');
  }
}
