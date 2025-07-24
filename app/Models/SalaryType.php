<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryType extends Model
{
  use HasFactory, InstitutionScope;

  protected $table = 'salary_types';
  protected $guarded = [];
  protected $casts = [
    'percentage' => 'float',
    'type' => TransactionType::class,
    'institution_id' => 'integer',
    'parent_id' => 'integer'
  ];

  // Scopes
  public function scopeCredit($query)
  {
    return $query->where('type', TransactionType::Credit);
  }

  public function scopeDebit($query)
  {
    return $query->where('type', TransactionType::Debit);
  }

  // Relationships
  public function parent(): BelongsTo
  {
    return $this->belongsTo(SalaryType::class, 'parent_id');
  }

  public function children(): HasMany
  {
    return $this->hasMany(SalaryType::class, 'parent_id');
  }

  public function salaries(): HasMany
  {
    return $this->hasMany(Salary::class);
  }

  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }
}
