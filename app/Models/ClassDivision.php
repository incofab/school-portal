<?php

namespace App\Models;

use App\Rules\ValidateUniqueRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

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
      ]
    ];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classifications()
  {
    return $this->belongsToMany(Classification::class, 'class_division_mappings');
  }
}
