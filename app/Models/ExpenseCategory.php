<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class ExpenseCategory extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer'
  ];

  static function createRule(
    Institution $institution,
    ?ExpenseCategory $expenseCategory = null
  ) {
    return [
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('expense_categories', 'title')
          ->where('institution_id', $institution->id)
          ->when(
            $expenseCategory,
            fn($q) => $q->ignore($expenseCategory->id, 'id')
          )
      ],
      'description' => ['nullable', 'string']
    ];
  }

  /**
   * Get the institution that owns the expense category.
   */
  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }

  /**
   * Get the expenses for this category.
   */
  public function expenses(): HasMany
  {
    return $this->hasMany(Expense::class, 'expense_category_id');
  }
}
