<?php

namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseResultInfo extends Model
{
  use HasFactory, InstitutionScope;
  public $table = 'course_result_info';

  protected $guarded = [];
  protected $casts = ['term' => TermType::class];

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
