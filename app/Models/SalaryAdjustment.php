<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryAdjustment extends Model
{
    use HasFactory, SoftDeletes, InstitutionScope;

    protected $table = 'salary_adjustments';
    protected $guarded = [];
    protected $casts = [
        'amount' => 'decimal:2',
        'year' => 'integer'
    ];
    protected $appends = ['actual_amount'];

    // Relationships
    public function adjustmentType(): BelongsTo
    {
        return $this->belongsTo(AdjustmentType::class);
    }

    public function institutionUser(): BelongsTo
    {
        return $this->belongsTo(InstitutionUser::class);
    }

    public function getActualAmountAttribute()
    {
        // If AdjustmentType does not have a ParentId, return the Amount.
        if ($this->adjustmentType->parent_id === null) {
            return $this->amount;
        }

        // If no amount and adjustment type has a parent with percentage
        if ($this->adjustmentType && $this->adjustmentType->parent_id && $this->adjustmentType->percentage) {
            // Find the parent adjustment for the same staff
            $parentAdjustment = self::where('institution_user_id', $this->institution_user_id)
                ->where('adjustment_type_id', $this->adjustmentType->parent_id)
                ->first();

            if ($parentAdjustment && $parentAdjustment->amount) {
                return ($parentAdjustment->amount * $this->adjustmentType->percentage) / 100;
            }
        }

        return 0; // Default to 0 if no calculation possible
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

    public function scopeCredit($query)
    {
        return $query->whereHas('adjustmentType', function ($q) {
            $q->where('type', 'credit');
        });
    }

    public function scopeDebit($query)
    {
        return $query->whereHas('adjustmentType', function ($q) {
            $q->where('type', 'debit');
        });
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    public function getPeriodAttribute(): string
    {
        return date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year));
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
}
