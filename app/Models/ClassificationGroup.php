<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;

class ClassificationGroup extends BaseModel
{
  use HasFactory, InstitutionScope;

  public const DEFAULT_HEAD_OF_SCHOOL_TITLE = 'Principal';
  public const DEFAULT_HEAD_OF_CLASS_TITLE = 'Form Teacher';
  public const DEFAULT_STUDENT_TITLE = 'Students';

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'show_class_group_position' => 'boolean'
  ];

  public static function createRule(
    Institution $institution,
    ?ClassificationGroup $classificationGroup = null
  ): array {
    return [
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classification_groups', 'title')
          ->where('institution_id', $institution->id)
          ->ignore($classificationGroup?->id, 'id')
      ],
      'head_of_school_title' => ['nullable', 'string', 'max:100'],
      'head_of_class_title' => ['nullable', 'string', 'max:100'],
      'student_title' => ['nullable', 'string', 'max:100']
    ];
  }

  public static function titleFallbacks(): array
  {
    return [
      'head_of_school_title' => self::DEFAULT_HEAD_OF_SCHOOL_TITLE,
      'head_of_class_title' => self::DEFAULT_HEAD_OF_CLASS_TITLE,
      'student_title' => self::DEFAULT_STUDENT_TITLE
    ];
  }

  public static function singularizeTitle(string $title): string
  {
    $title = trim($title);
    $lower = strtolower($title);

    if (str_ends_with($lower, 'ies')) {
      return substr($title, 0, -3) . 'y';
    }

    if (str_ends_with($lower, 's') && !str_ends_with($lower, 'ss')) {
      return substr($title, 0, -1);
    }

    return $title;
  }

  public static function possessiveTitle(string $title): string
  {
    return str_ends_with($title, 's') ? "{$title}'" : "{$title}'s";
  }

  public function studentSingularTitle(): string
  {
    return self::singularizeTitle($this->student_title);
  }

  public function studentPossessiveTitle(): string
  {
    return self::possessiveTitle($this->studentSingularTitle());
  }

  protected function headOfSchoolTitle(): Attribute
  {
    return $this->titleWithFallback(self::DEFAULT_HEAD_OF_SCHOOL_TITLE);
  }

  protected function headOfClassTitle(): Attribute
  {
    return $this->titleWithFallback(self::DEFAULT_HEAD_OF_CLASS_TITLE);
  }

  protected function studentTitle(): Attribute
  {
    return $this->titleWithFallback(self::DEFAULT_STUDENT_TITLE);
  }

  private function titleWithFallback(string $fallback)
  {
    return Attribute::make(
      get: fn($value) => filled($value) ? $value : $fallback,
      set: fn($value) => filled($value) ? trim($value) : $fallback
    );
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classifications()
  {
    return $this->hasMany(Classification::class);
  }
}
