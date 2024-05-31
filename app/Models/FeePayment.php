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
    'user_id' => 'integer',
    'fee_id' => 'integer',
    'academic_session_id' => 'integer',
    'receipt_id' => 'integer',
    'recorded_by_user_id' => 'integer'
  ];

  function fee()
  {
    return $this->belongsTo(Fee::class);
  }
  function user()
  {
    return $this->belongsTo(User::class);
  }
  function receipt()
  {
    return $this->belongsTo(Receipt::class);
  }
  function recordedBy()
  {
    return $this->belongsTo(User::class, 'recorded_by_user_id');
  }
  function feePaymentTracks()
  {
    return $this->hasMany(FeePaymentTrack::class);
  }
  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
