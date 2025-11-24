<?php

namespace App\Models;

use App\Enums\ResultCommentTemplateType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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

  /**
   * Get result comment templates filtered by classification and type
   * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ResultCommentTemplate>
   */
  static function getTemplate(
    Classification|int|null $classification = null,
    ?bool $forMidTerm = false
  ) {
    $resultComments = ResultCommentTemplate::query()
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

    return $resultComments
      ->filter(function (ResultCommentTemplate $item) use ($classification) {
        if ($item->classifications->isEmpty()) {
          return true;
        }
        $id = is_int($classification) ? $classification : $classification->id;
        return $item->classifications->contains('id', $id);
      })
      ->values();
  }

  public function classifications(): MorphToMany
  {
    return $this->morphToMany(
      Classification::class,
      'classifiable',
      'classifiables'
    );
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
