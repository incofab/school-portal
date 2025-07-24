<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollAdjustment extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'amount' => 'float',
    'institution_id' => 'integer',
    'institution_user_id' => 'integer',
    'payroll_adjustment_type_id' => 'integer',
    'payroll_summary_id' => 'integer'
  ];

  // Scopes
  public function scopeByUser($query, $userId)
  {
    return $query->where('institution_user_id', $userId);
  }

  // Relationships
  public function payrollAdjustmentType(): BelongsTo
  {
    return $this->belongsTo(PayrollAdjustmentType::class);
  }

  public function institutionUser(): BelongsTo
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  public function payrollSummary(): BelongsTo
  {
    return $this->belongsTo(PayrollSummary::class);
  }

  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }
}
