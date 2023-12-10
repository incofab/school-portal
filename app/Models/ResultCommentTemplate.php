<?php

namespace App\Models;

use App\Enums\ResultCommentTemplateType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultCommentTemplate extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'type' => ResultCommentTemplateType::class
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
