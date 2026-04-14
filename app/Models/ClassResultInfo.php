<?php

namespace App\Models;

use App\Enums\TermType;
use App\Support\UITableFilters\TermResultUITableFilters;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassResultInfo extends Model
{
  use HasFactory, InstitutionScope;
  public $table = 'class_result_info';

  /** @var array<string, bool> */
  private static array $resultLockCache = [];

  protected $guarded = [];
  protected $casts = [
    'term' => TermType::class,
    'teacher_user_id' => 'integer',
    'institution_id' => 'integer',
    'academic_session_id' => 'integer',
    'classification_id' => 'integer',
    'num_of_students' => 'integer',
    'num_of_courses' => 'integer',
    'for_mid_term' => 'boolean',
    'is_locked' => 'boolean',
    'next_term_resumption_date' => 'date',
    'whatsapp_message_count' => 'integer'
  ];

  public static function ensureResultIsUnlocked(
    int $classificationId,
    int $academicSessionId,
    string|TermType $term,
    bool $forMidTerm
  ): void {
    $termValue = $term instanceof TermType ? $term->value : $term;
    $cacheKey = implode(':', [
      $classificationId,
      $academicSessionId,
      $termValue,
      (int) $forMidTerm
    ]);

    if (array_key_exists($cacheKey, static::$resultLockCache)) {
      $isLocked = static::$resultLockCache[$cacheKey];
    } else {
      $isLocked = static::query()
        ->where('classification_id', $classificationId)
        ->where('academic_session_id', $academicSessionId)
        ->where('term', $termValue)
        ->where('for_mid_term', $forMidTerm)
        ->where('is_locked', true)
        ->exists();

      static::$resultLockCache[$cacheKey] = $isLocked;
    }

    abort_if(
      $isLocked,
      423,
      'This class result is locked. Unlock it from Class Result Analysis before adding or editing results.'
    );
  }

  public static function clearResultLockCache(): void
  {
    static::$resultLockCache = [];
  }

  /**
   * @return \Illuminate\Database\Eloquent\Builder<\App\Models\CourseResult>
   */
  function courseResultsQuery()
  {
    return CourseResult::query()->where([
      'course_results.academic_session_id' => $this->academic_session_id,
      'course_results.classification_id' => $this->classification_id,
      'course_results.term' => $this->term,
      'course_results.for_mid_term' => $this->for_mid_term
    ]);
  }

  /**
   * @param callable(\App\Support\UITableFilters\TermResultUITableFilters): void $extraQueryCallback
   * @return \Illuminate\Database\Eloquent\Builder<\App\Models\TermResult>
   */
  function termResultsQuery(?callable $extraQueryCallback = null)
  {
    $params = [
      'classification' => $this->classification_id,
      'term' => $this->term,
      'academicSession' => $this->academic_session_id,
      'forMidTerm' => $this->for_mid_term
    ];
    $termResultQuery = TermResultUITableFilters::make(
      [...$params, ...request()->all()],
      TermResult::query()->select('term_results.*')
    );
    if ($extraQueryCallback) {
      $extraQueryCallback($termResultQuery);
    }
    return $termResultQuery->filterQuery()->getQuery();
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
