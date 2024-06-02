<?php

namespace App\Models;

use App\Enums\PaymentInterval;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  public $casts = ['payment_interval' => PaymentInterval::class];

  function receiptType()
  {
    return $this->belongsTo(ReceiptType::class);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
