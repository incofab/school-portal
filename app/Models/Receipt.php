<?php

namespace App\Models;

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
    'receipt_type_id' => 'integer',
    'academic_session_id' => 'integer',
    'classification_id' => 'integer',
    'classification_group_id' => 'integer',
    'approved_by_user_id' => 'integer',
    'term' => TermType::class
  ];

  function receiptType()
  {
    return $this->belongsTo(Fee::class, 'receipt_type_id');
  }
  function user()
  {
    return $this->belongsTo(User::class);
  }
  function approvedBy()
  {
    return $this->belongsTo(User::class, 'approved_by_user_id');
  }
  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
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
