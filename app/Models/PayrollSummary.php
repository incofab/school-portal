<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollSummary extends Model
{
  use HasFactory, InstitutionScope;

  protected $table = 'payroll_summaries';
  protected $guarded = [];
  protected $casts = [
    'amount' => 'decimal:2',
    'total_deduction' => 'decimal:2',
    'total_bonuses' => 'decimal:2',
    'year' => 'integer',
    'evaluated_at' => 'date'
  ];

  // Relationships
  public function payrolls(): HasMany
  {
    return $this->hasMany(Payroll::class);
  }

  // Scopes
  public function scopeByInstitution($query, $institutionId)
  {
    return $query->where('institution_id', $institutionId);
  }

  public function scopeByMonth($query, $month)
  {
    return $query->where('month', $month);
  }

  public function scopeByYear($query, $year)
  {
    return $query->where('year', $year);
  }

  public function scopeByPeriod($query, $month, $year)
  {
    return $query->where('month', $month)->where('year', $year);
  }

  // Accessors
  public function getFormattedAmountAttribute(): string
  {
    return number_format($this->amount, 2);
  }

  public function getFormattedTotalDeductionAttribute(): string
  {
    return number_format($this->total_deduction, 2);
  }

  public function getFormattedTotalBonusesAttribute(): string
  {
    return number_format($this->total_bonuses, 2);
  }

  public function getPeriodAttribute(): string
  {
    return date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year));
  }

  public function getMonthNameAttribute(): string
  {
    return date('F', mktime(0, 0, 0, $this->month, 1));
  }

  public function getNetAmountAttribute(): float
  {
    return $this->amount - $this->total_deduction + $this->total_bonuses;
  }

  public function getFormattedNetAmountAttribute(): string
  {
    return number_format($this->net_amount, 2);
  }
}
