<?php
namespace App\Models;

use App\Enums\Term;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermResult extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  public $casts = ['term' => Term::class];

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
