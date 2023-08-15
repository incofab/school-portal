<?php

namespace App\Models;

use App\Support\Queries\InstructionQueryBuilder;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  public function newEloquentBuilder($query)
  {
    return new InstructionQueryBuilder($query);
  }

  static function createRule()
  {
    return [
      'from' => ['required', 'integer'],
      'to' => ['required', 'integer', 'gte:from'],
      'instruction' => ['required', 'string']
    ];
  }

  static function multiInsert(CourseSession $courseSession, array $instructions)
  {
    foreach ($instructions as $key => $instruction) {
      $courseSession->instructions()->firstOrCreate(
        [
          'institution_id' => $courseSession->institution_id,
          'from' => $instruction['from_'],
          'to' => $instruction['to_']
        ],
        ['instruction' => $instruction['instruction']]
      );
    }
  }

  function courseable()
  {
    return $this->morphTo();
  }
}
