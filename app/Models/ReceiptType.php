<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptType extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  public $casts = ['institution_id' => 'integer'];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
