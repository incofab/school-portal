<?php

namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassResultInfo extends Model
{
  use HasFactory, InstitutionScope;
  public $table = 'class_result_info';

  protected $guarded = [];
  protected $casts = ['term' => TermType::class];

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
