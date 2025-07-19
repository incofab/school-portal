<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payroll model
 * @property array{
 *  salaries: array<string, float>
 * } $meta
 */
class Payroll extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $table = 'payroll';
  protected $guarded = [];
  protected $casts = [
    'total_salary' => 'decimal:2',
    'tax' => 'decimal:2',
    'net_salary' => 'decimal:2',
    'total_deductions' => 'decimal:2',
    'total_bonuses' => 'decimal:2',
    'meta' => AsArrayObject::class
  ];

  // Relationships
  public function payrollSummary(): BelongsTo
  {
    return $this->belongsTo(PayrollSummary::class);
  }

  // Note: Assuming you have an InstitutionUser model
  public function institutionUser(): BelongsTo
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  // Scopes
  public function scopeByInstitution($query, $institutionId)
  {
    return $query->where('institution_id', $institutionId);
  }

  public function scopeByUser($query, $userId)
  {
    return $query->where('institution_user_id', $userId);
  }

  public function scopeBySummary($query, $summaryId)
  {
    return $query->where('payroll_summary_id', $summaryId);
  }

  // Accessors
  public function getFormattedNetAmountAttribute(): string
  {
    return number_format($this->net_amount, 2);
  }

  public function getFormattedTotalDeductionsAttribute(): string
  {
    return number_format($this->total_deductions, 2);
  }

  public function getFormattedTotalBonusesAttribute(): string
  {
    return number_format($this->total_bonuses, 2);
  }

  public function getFormattedIncomeAttribute(): string
  {
    return number_format($this->income, 2);
  }

  public function getGrossAmountAttribute(): float
  {
    return $this->income + $this->total_bonuses;
  }

  public function getFormattedGrossAmountAttribute(): string
  {
    return number_format($this->gross_amount, 2);
  }

  // Get period from related payroll summary
  public function getPeriodAttribute(): string
  {
    return $this->payrollSummary ? $this->payrollSummary->period : '';
  }
}
