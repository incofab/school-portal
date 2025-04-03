<?php

namespace App\Models;

use App\Support\Queries\PassageQueryBuilder;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passage extends Model
{
  use HasFactory, InstitutionScope;
  protected $guarded = [];

  public function newEloquentBuilder($query)
  {
    return new PassageQueryBuilder($query);
  }

  static function createRule()
  {
    return [
      'from' => ['required', 'integer'],
      'to' => ['required', 'integer', 'gte:from'],
      'passage' => ['required', 'string'],
      'courseable_id' => ['required']
    ];
  }

  static function multiInsert(CourseSession $courseSession, array $passages)
  {
    foreach ($passages as $key => $passage) {
      $courseSession->passages()->firstOrCreate(
        [
          'institution_id' => $courseSession->institution_id,
          'from' => $passage['from_'],
          'to' => $passage['to_']
        ],
        ['passage' => $passage['passage']]
      );
    }
  }

  // function session()
  // {
  //   return $this->belongsTo(CourseSession::class);
  // }

  function courseable()
  {
    return $this->morphTo();
  }
}
