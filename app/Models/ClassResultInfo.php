<?php

namespace App\Models;

use App\Enums\TermType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassResultInfo extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = ['term' => TermType::class];

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
