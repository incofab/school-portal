<?php

namespace App\Models;

use App\Enums\ResultTemplateType;
use App\Rules\ValidateExistsRule;
use App\Rules\ValidateUniqueRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rules\Enum;

class ClassDivision extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer'
  ];

  static function createRule(?ClassDivision $classDivision = null)
  {
    return [
      'title' => [
        'required',
        'string',
        'max:100',
        (new ValidateUniqueRule(ClassDivision::class, 'title'))->when(
          $classDivision,
          fn($q) => $q->ignore($classDivision->id, 'id')
        )
      ],
      'result_template' => ['nullable', new Enum(ResultTemplateType::class)],
      'classification_ids' => ['sometimes', 'array'],
      'classification_ids.*' => [
        'integer',
        new ValidateExistsRule(Classification::class)
      ]
    ];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classifications()
  {
    return $this->morphedByMany(
      Classification::class,
      'mappable',
      'class_division_mappings'
    );
  }
  function assessments()
  {
    return $this->morphedByMany(
      Assessment::class,
      'mappable',
      'class_division_mappings'
    );
  }
}
