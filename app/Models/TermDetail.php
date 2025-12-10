<?php
namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermDetail extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  protected $casts = [
    'term' => TermType::class,
    'institution_id' => 'integer',
    'academic_session_id' => 'integer',
    'for_mid_term' => 'boolean',
    'expected_attendance_count' => 'integer',
    'inactive_weekdays' => 'array',
    'special_active_days' => 'array',
    'inactive_days' => 'array',
    'start_date' => 'date',
    'end_date' => 'date',
    'next_term_resumption_date' => 'date',
    'is_activated' => 'boolean'
  ];

  protected $attributes = [
    'inactive_weekdays' => '[]',
    'special_active_days' => '[]',
    'inactive_days' => '[]'
  ];

  function scopeForTermResult($query, TermResult $termResult)
  {
    return $query->where([
      'academic_session_id' => $termResult->academic_session_id,
      'term' => $termResult->term,
      'for_mid_term' => $termResult->for_mid_term
    ]);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }

  public function isActiveOnDate(Carbon|string $date): bool
  {
    $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
    $dateStr = $date->toDateString();

    $inactiveDays = collect($this->inactive_days ?? [])
      ->pluck('date')
      ->filter()
      ->map(fn($day) => Carbon::parse($day)->toDateString())
      ->all();

    if (in_array($dateStr, $inactiveDays)) {
      return false;
    }

    $specialActiveDays = collect($this->special_active_days ?? [])
      ->pluck('date')
      ->filter()
      ->map(fn($day) => Carbon::parse($day)->toDateString())
      ->all();
    if (in_array($dateStr, $specialActiveDays)) {
      return true;
    }

    $inactiveWeekdays = collect($this->inactive_weekdays ?? [])
      ->map(fn($day) => intval($day))
      ->filter(fn($day) => $day >= 0 && $day <= 6)
      ->all();
    $projectWeekday = ($date->dayOfWeek + 6) % 7; // Map Carbon Sunday=0..Saturday=6 to project Monday=0..Sunday=6
    if (in_array($projectWeekday, $inactiveWeekdays)) {
      return false;
    }

    return true;
  }
}
