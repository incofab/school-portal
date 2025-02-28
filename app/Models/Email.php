<?php

namespace App\Models;

use App\Enums\EmailRecipientType;
use App\Enums\EmailStatus;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
  use HasFactory, InstitutionScope;

  protected $casts = [
    'institution_id' => 'integer',
    'sender_user_id' => 'integer',
    'type' => EmailRecipientType::class,
    'status' => EmailStatus::class
  ];
  protected $guarded = [];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function sender()
  {
    return $this->belongsTo(User::class);
  }

  function recipients()
  {
    return $this->hasMany(EmailRecipient::class);
  }

  // SchoolNotification
  function messageable()
  {
    return $this->morphTo('messageable');
  }
} 
