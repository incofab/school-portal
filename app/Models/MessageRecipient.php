<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageRecipient extends BaseModel
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'recipient_id' => 'integer'
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function message()
  {
    return $this->belongsTo(Message::class);
  }
}
