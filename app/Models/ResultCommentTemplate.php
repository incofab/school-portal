<?php

namespace App\Models;

use App\Enums\ResultCommentTemplateType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultCommentTemplate extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'min' => 'float',
    'max' => 'float',
    'type' => ResultCommentTemplateType::class
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
