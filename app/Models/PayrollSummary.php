<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSummary extends Model
{
  use HasFactory, InstitutionScope;

  protected $table = 'payroll_summaries';
  protected $guarded = [];
  protected $casts = [
    'amount' => 'float',
    'total_deduction' => 'float',
    'total_bonuses' => 'float',
    'year' => 'integer',
    'evaluated_at' => 'date'
  ];

  static function getPayrollSummary($institutionId, $month, $year): static
  {
    return PayrollSummary::query()->firstOrCreate([
      'institution_id' => $institutionId,
      'month' => $month,
      'year' => $year
    ]);
  }

  function isEvaluated(): bool
  {
    return $this->evaluated_at !== null;
  }

  function isNotEvaluated(): bool
  {
    return $this->evaluated_at === null;
  }

  // Scopes
  public function scopeIsEvaluated($query)
  {
    return $query->whereNotNull('evaluated_at');
  }
  public function scopeNotEvaluated($query)
  {
    return $query->whereNull('evaluated_at');
  }

  // Relationships
  public function payrolls(): HasMany
  {
    return $this->hasMany(Payroll::class);
  }

  public function payrollAdjustments(): HasMany
  {
    return $this->hasMany(PayrollAdjustment::class);
  }

  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }
}
