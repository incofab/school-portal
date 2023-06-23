<?php
namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionResult extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  protected $casts = ['term' => TermType::class];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function student()
  {
    return $this->belongsTo(Student::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
