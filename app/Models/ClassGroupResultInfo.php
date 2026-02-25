<?php

namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroupResultInfo extends Model
{
  use HasFactory, InstitutionScope;
  public $table = 'class_group_result_info';

  protected $guarded = [];
  protected $casts = [
    'term' => TermType::class,
    'institution_id' => 'integer',
    'academic_session_id' => 'integer',
    'classification_group_id' => 'integer',
    'num_of_students' => 'integer',
    'for_mid_term' => 'boolean',
    'total_score' => 'float',
    'max_score' => 'float',
    'min_score' => 'float',
    'average' => 'float',
    'max_obtainable_score' => 'float'
  ];

  public function classificationGroup()
  {
    return $this->belongsTo(ClassificationGroup::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
