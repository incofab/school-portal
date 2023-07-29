<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  static function generatePin()
  {
    $prefix = substr(date('Y'), 2);

    $key = $prefix . mt_rand(1000000000, 9999999999);

    while (Student::where('code', '=', $key)->first()) {
      $key = $prefix . mt_rand(1000000000, 9999999999);
    }

    return $key;
  }

  function isUsed()
  {
    return !empty($this->used_at);
  }

  function scopeUsed($query, $forUsed = true)
  {
    return $forUsed
      ? $query->whereNotNull('used_at')
      : $query->whereNull('used_at');
  }

  function scopePrinted($query, $isPrinted = true)
  {
    return $isPrinted
      ? $query->whereNotNull('pin_print_id')
      : $query->whereNull('pin_print_id');
  }

  function termResults()
  {
    return $this->hasMany(TermResult::class);
  }
  function pinGenerator()
  {
    return $this->hasOne(PinGenerator::class);
  }
  function pinPrint()
  {
    return $this->hasOne(PinPrint::class);
  }
  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
