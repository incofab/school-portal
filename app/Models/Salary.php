<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = ['amount' => 'float'];

  // Scopes
  public function scopeByUser($query, $userId)
  {
    return $query->where('institution_user_id', $userId);
  }

  public function scopeByType($query, $typeId)
  {
    return $query->where('salary_type_id', $typeId);
  }

  // Relationships
  public function salaryType(): BelongsTo
  {
    return $this->belongsTo(SalaryType::class);
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
