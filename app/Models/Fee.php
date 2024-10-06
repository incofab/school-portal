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
  public $casts = [
    'payment_interval' => PaymentInterval::class,
    'institution_id' => 'integer',
    'receipt_type_id' => 'integer',
    'classification_id' => 'integer',
    'classification_group_id' => 'integer'
  ];

  function isSessional(): bool
  {
    return $this->payment_interval === PaymentInterval::Termly ||
      $this->payment_interval === PaymentInterval::Sessional;
  }

  function isTermly(): bool
  {
    return $this->payment_interval === PaymentInterval::Termly;
  }

  function receiptType()
  {
    return $this->belongsTo(ReceiptType::class);
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function classificationGroup()
  {
    return $this->belongsTo(ClassificationGroup::class);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
