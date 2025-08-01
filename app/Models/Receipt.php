<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  public $casts = [
    'institution_id' => 'integer',
    'user_id' => 'integer',
    'fee_id' => 'integer',
    'amount_remaining' => 'float',
    'amount_paid' => 'float',
    'amount' => 'float',
    'term' => TermType::class,
    'academic_session_id' => 'integer',
    'status' => ReceiptStatus::class
  ];

  function paymentsSum()
  {
    return $this->feePayments->sum(fn($item) => $item->amount);
  }

  function hasPaid()
  {
    return $this->status === ReceiptStatus::Paid && $this->amount_remaining < 1;
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }

  function fee()
  {
    return $this->belongsTo(Fee::class);
  }

  function feePayments()
  {
    return $this->hasMany(FeePayment::class);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
