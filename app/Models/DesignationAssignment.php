<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DesignationAssignment extends BaseModel
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'assigned_at' => 'datetime',
    'ended_at' => 'datetime'
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function designation()
  {
    return $this->belongsTo(Designation::class);
  }

  public function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  public function student()
  {
    return $this->belongsTo(
      Student::class,
      'institution_user_id',
      'institution_user_id'
    );
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }

  public function assignedBy()
  {
    return $this->belongsTo(User::class, 'assigned_by_user_id');
  }
}
