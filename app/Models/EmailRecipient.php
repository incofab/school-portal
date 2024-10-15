<?php

namespace App\Models;

use App\Enums\EmailRecipientType;
use App\Enums\EmailStatus;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailRecipient extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'recipient_id' => 'integer',
    'type' => EmailRecipientType::class,
    'status' => EmailStatus::class
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function email()
  {
    return $this->belongsTo(Email::class);
  }
}
