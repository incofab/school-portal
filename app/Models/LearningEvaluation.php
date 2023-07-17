<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningEvaluation extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];

  public $casts = [
    'institution_id' => 'integer',
    'learning_evaluation_domain_id' => 'integer'
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function learningEvaluationDomain()
  {
    return $this->belongsTo(LearningEvaluationDomain::class);
  }
}
