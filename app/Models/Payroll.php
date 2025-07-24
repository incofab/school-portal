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
    'gross_salary' => 'float',
    'tax' => 'float',
    'net_salary' => 'float',
    'total_deductions' => 'float',
    'total_bonuses' => 'float',
    'meta' => AsArrayObject::class
  ];

  // Scopes
  public function scopeByUser($query, $userId)
  {
    return $query->where('institution_user_id', $userId);
  }

  public function scopeBySummary($query, $summaryId)
  {
    return $query->where('payroll_summary_id', $summaryId);
  }

  // Relationships
  public function payrollSummary(): BelongsTo
  {
    return $this->belongsTo(PayrollSummary::class);
  }

  public function institutionUser(): BelongsTo
  {
    return $this->belongsTo(InstitutionUser::class);
  }
  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }
}
