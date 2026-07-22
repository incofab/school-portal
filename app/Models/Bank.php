<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends BaseModel
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
}
