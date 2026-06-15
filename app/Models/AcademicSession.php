<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AcademicSession extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];

  protected $casts = [
    'order_index' => 'integer',
    'is_active' => 'boolean'
  ];

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
