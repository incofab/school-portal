<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'from' => 'integer',
    'to' => 'integer'
  ];

  static function createRule()
  {
    return [
      'from' => ['required', 'integer'],
      'to' => ['required', 'integer', 'gte:from'],
      'instruction' => ['required', 'string']
    ];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function courseable()
  {
    return $this->morphTo();
  }
}
