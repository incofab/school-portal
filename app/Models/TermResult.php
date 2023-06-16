<?php
namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermResult extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function scopeActivated($query, $isActivated = true)
  {
    return $query->where('is_activated', $isActivated);
  }

  function student()
  {
    return $this->belongsTo(Student::class);
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
