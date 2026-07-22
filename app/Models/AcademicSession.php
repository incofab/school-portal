<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AcademicSession extends BaseModel
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];

  protected $casts = [
    'order_index' => 'integer',
    'is_active' => 'boolean'
  ];

  public function scopeActive($query)
  {
    return $query->where('is_active', true)->first();
  }

  public function activate(): static
  {
    DB::transaction(function () {
      self::query()
        ->whereKeyNot($this->getKey())
        ->update(['is_active' => false]);

      $this->forceFill(['is_active' => true])->save();
    });

    return $this->refresh();
  }
}
