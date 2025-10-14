<?php

namespace App\Models;

use App\Enums\FullTermType;
use App\Support\Queries\AssessmentQueryBuilder;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
  // 08030583452 - decency garden estate
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  const PREFIX = 'ass.';

  public $casts = [
    'institution_id' => 'integer',
    'for_mid_term' => 'boolean',
    'depends_on' => FullTermType::class
  ];

  protected $appends = ['raw_title'];

  public static function query(): AssessmentQueryBuilder
  {
    return parent::query();
  }

  public function newEloquentBuilder($query)
  {
    return new AssessmentQueryBuilder($query);
  }

  protected function title(): Attribute
  {
    return Attribute::make(
      get: fn(string $value) => str_replace(['_'], [' '], $value),
      set: fn(string $value) => str_replace(
        ['  ', ' '],
        '_',
        strtolower($value)
      )
    );
  }

  /** Column title to be used in Excel and Table columns */
  function columnTitle(bool $rawOriginal = true): string
  {
    return self::PREFIX .
      ($rawOriginal ? $this->getRawOriginal('title') : $this->title);
  }

  protected function rawTitle(): Attribute
  {
    return Attribute::make(get: fn() => $this->getRawOriginal('title'));
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classDivisions()
  {
    return $this->morphToMany(ClassDivision::class, 'mappable', 'class_division_mappings');
  }
}
