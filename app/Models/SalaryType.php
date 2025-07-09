<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryType extends Model
{
    use HasFactory, SoftDeletes, InstitutionScope;

    protected $table = 'salary_types';
    protected $guarded = [];
    protected $casts = [
        'type' => 'string'
    ];

    // Relationships 
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SalaryType::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SalaryType::class, 'parent_id');
    }

    public function staffSalaries(): HasMany
    {
        return $this->hasMany(StaffSalary::class);
    }

    // Scopes
    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeByInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    // Accessors
    public function getIsParentAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->count() > 0;
    }
}
