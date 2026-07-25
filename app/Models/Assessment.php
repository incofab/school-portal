<?php

namespace App\Models;

use App\Enums\FullTermType;
use App\Enums\TermType;
use App\Rules\ValidateExistsRule;
use App\Support\Queries\AssessmentQueryBuilder;
use App\Support\SettingsHandler;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rules\Enum;

class Assessment extends BaseModel
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  const PREFIX = 'ass.';

  public $casts = [
    'institution_id' => 'integer',
    'for_mid_term' => 'boolean',
    'depends_on' => FullTermType::class,
    'max' => 'integer'
  ];

  protected $appends = ['raw_title', 'result_key'];

  static function createRule(?Assessment $assement = null)
  {
    return [
      'term' => ['nullable', new Enum(TermType::class)],
      'for_mid_term' => ['nullable', 'boolean'],
      'title' => ['required', 'max:255'],
      'max' => ['required', 'numeric', 'min:0', 'max:100'],
      'description' => ['nullable', 'string'],
      'classification_ids' => ['nullable', 'array'],
      'classification_ids.*' => [
        'integer',
        new ValidateExistsRule(Classification::class)
      ]
    ];
  }

  public static function query(): AssessmentQueryBuilder
  {
    return parent::query();
  }

  public function newEloquentBuilder($query)
  {
    return new AssessmentQueryBuilder($query);
  }

  /**
   * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assessment>
   */
  static function getAssessments(
    string|TermType|null $term = null,
    ?bool $forMidTerm = false,
    Classification|int|null $classification = null,
    bool $withTrashed = false
  ) {
    $query = Assessment::query();
    if ($withTrashed) {
      $query->withTrashed();
    }

    $assements = $query
      ->forTerm($term)
      ->forMidTerm($forMidTerm)
      ->with('classifications')
      ->get();
    if (!$classification) {
      return $assements;
    }
    return $assements
      ->filter(function (Assessment $item) use ($classification) {
        if ($item->classifications->isEmpty()) {
          return true;
        }
        $id = is_int($classification) ? $classification : $classification->id;
        return $item->classifications->contains('id', $id);
      })
      ->values();
  }

  static function getAssessmentGroups(
    string|TermType|null $term,
    Classification|int|null $classification = null
  ): array {
    $term ??= SettingsHandler::makeFromRoute()->getCurrentTerm();

    return [
      'fullTerm' => self::getAssessments($term, false, $classification),
      'midTerm' => self::getAssessments($term, true, $classification)
    ];
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

  protected function resultKey(): Attribute
  {
    return Attribute::make(get: fn() => $this->assessmentResultKey());
  }

  public function assessmentResultKey(): string
  {
    return self::makeResultKey($this->raw_title, $this->id);
  }

  public static function makeResultKey(string $title, int|string $id): string
  {
    return "{$title}|{$id}";
  }

  public static function resultKeyId(string $key): ?int
  {
    $separatorPosition = strrpos($key, '|');
    if ($separatorPosition === false) {
      return null;
    }

    $id = substr($key, $separatorPosition + 1);
    return ctype_digit($id) ? (int) $id : null;
  }

  public static function assessmentScoreFromValues(
    array|\ArrayAccess|null $values,
    Assessment $assessment,
    int|float|string|null $default = 0
  ): int|float|string|null {
    if (!$values) {
      return $default;
    }

    $resultKey = $assessment->assessmentResultKey();
    if (isset($values[$resultKey])) {
      return $values[$resultKey];
    }

    $legacyKey = $assessment->raw_title;
    if (isset($values[$legacyKey])) {
      return $values[$legacyKey];
    }

    foreach ($values as $key => $value) {
      if (is_string($key) && self::resultKeyId($key) === $assessment->id) {
        return $value;
      }
    }

    return $default;
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  /** @deprecated */
  function classDivisions()
  {
    return $this->morphToMany(
      ClassDivision::class,
      'mappable',
      'class_division_mappings'
    );
  }

  public function classifications(): MorphToMany
  {
    return $this->morphToMany(
      Classification::class,
      'classifiable',
      'classifiables'
    );
  }
}
