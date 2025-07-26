<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollAdjustmentType extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
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

  public function scopeParents($query)
  {
    return $query->whereNull('parent_id');
  }

  // Relationships
  public function parent(): BelongsTo
  {
    return $this->belongsTo(PayrollAdjustmentType::class, 'parent_id');
  }

  public function children(): HasMany
  {
    return $this->hasMany(PayrollAdjustmentType::class, 'parent_id');
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
