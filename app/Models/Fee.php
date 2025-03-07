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

  static function scopeForClass($query, Classification $classification)
  {
    return $query->where(function ($qq) use ($classification) {
      $qq
        ->where(fn ($q) => 
          $q->whereNull('classification_group_id')->whereNull(
            'classification_id'
          )
        )
        ->orWhere(fn ($q) => 
          $q->whereNotNull('classification_group_id')->where(
            'classification_group_id',
            $classification->classification_group_id
          )
        )
        ->orWhere(fn ($q) => 
          $q->whereNull('classification_group_id')->where(
            'classification_id',
            $classification->id
          )
        );
    });
  }

  static function scopeForReceiptType($query, ReceiptType|null $receiptType = null)
  {
    return $query->when($receiptType, fn($q) => $q->where('receipt_type_id', $receiptType->id));
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

  function feePayments()
  {
    return $this->hasMany(FeePayment::class);
  }
}
