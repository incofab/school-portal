<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class LiveClass extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'teacher_user_id' => 'integer',
    'is_active' => 'boolean',
    'starts_at' => 'datetime',
    'ends_at' => 'datetime'
  ];

  static function createRule()
  {
    return [
      'title' => ['required', 'string', 'max:255'],
      'meet_url' => ['required', 'url', 'max:255'],
      'liveable_type' => [
        'required',
        Rule::in([
          Classification::class,
          ClassificationGroup::class,
          ClassDivision::class
        ])
      ],
      'liveable_id' => ['required', 'integer'],
      'starts_at' => ['nullable', 'date'],
      'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
      'is_active' => ['nullable', 'boolean']
    ];
  }

  public function liveable()
  {
    return $this->morphTo();
  }

  public function teacher()
  {
    return $this->belongsTo(User::class, 'teacher_user_id');
  }

  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }
}
