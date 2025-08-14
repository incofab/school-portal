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

  static function getTemplate(?bool $forMidTerm = false)
  {
    return ResultCommentTemplate::query()
      ->where(
        fn($q) => $q
          ->whereNull('type')
          ->orWhere(
            'type',
            $forMidTerm
              ? ResultCommentTemplateType::MidTermResult
              : ResultCommentTemplateType::FullTermResult
          )
      )
      ->get();
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
