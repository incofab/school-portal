<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffSalary extends Model
{
    use HasFactory, SoftDeletes, InstitutionScope;

    protected $table = 'staff_salaries';
    protected $guarded = [];
    protected $casts = ['amount' => 'decimal:2'];
    protected $appends = ['actual_amount'];

    // Relationships
    public function salaryType(): BelongsTo
    {
        return $this->belongsTo(SalaryType::class);
    }

    public function institutionUser(): BelongsTo
    {
        return $this->belongsTo(InstitutionUser::class);
    }

    public function getActualAmountAttribute()
    {
        // If SalaryType does not have a ParentId, return the Amount.
        if ($this->salaryType->parent_id === null) {
            return $this->amount;
        }

        // If no amount and salary type has a parent with percentage
        if ($this->salaryType && $this->salaryType->parent_id && $this->salaryType->percentage) {
            // Find the parent salary for the same staff
            $parentSalary = self::where('institution_user_id', $this->institution_user_id)
                ->where('salary_type_id', $this->salaryType->parent_id)
                ->first();

            if ($parentSalary && $parentSalary->amount) {
                return ($parentSalary->amount * $this->salaryType->percentage) / 100;
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

    public function scopeByType($query, $typeId)
    {
        return $query->where('salary_type_id', $typeId);
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }
}
