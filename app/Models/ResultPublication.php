<?php

namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPublication extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'institution_group_id' => 'integer',
    'institution_id' => 'integer',
    'num_of_results' => 'integer',
    'academic_session_id' => 'integer',
    'staff_user_id' => 'integer',
    'num_of_students' => 'integer',
    'term' => TermType::class
  ];

  public function staff()
  {
    return $this->belongsTo(User::class, 'staff_user_id');
  }
  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
  public function transaction()
  {
    return $this->morphOne(Transaction::class, 'transactionable');
  }
}
