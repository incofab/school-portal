<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Association extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  public $casts = [
    'institution_id' => 'integer'
  ];

  static function createRule(
    Institution $institution,
    ?Association $association = null
  ) {
    return [
      'institution_id' => ['required', 'integer'],
      'title' => [
        'required',
        Rule::unique('associations', 'title')
          ->where('institution_id', $institution->id)
          ->when($association, fn($q) => $q->ignore($association->id, 'id'))
      ],
      'description' => ['nullable', 'string']
    ];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function userAssociations()
  {
    return $this->hasMany(UserAssociation::class);
  }
}
