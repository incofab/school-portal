<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'amount' => 'float',
    'academic_session_id' => 'integer',
    'expense_date' => 'date',
    'expense_category_id' => 'integer',
    'created_by' => 'integer'
  ];

  /**
   * Get the institution that owns the expense.
   */
  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }

  /**
   * Get the academic session for this expense.
   */
  public function academicSession(): BelongsTo
  {
    return $this->belongsTo(AcademicSession::class);
  }

  /**
   * Get the expense category.
   */
  public function expenseCategory(): BelongsTo
  {
    return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
  }

  /**
   * Get the user who created this expense.
   */
  public function institutionUser(): BelongsTo
  {
    return $this->belongsTo(InstitutionUser::class, 'created_by');
  }
}
