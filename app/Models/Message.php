<?php

namespace App\Models;

use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends BaseModel
{
  use HasFactory, InstitutionScope;

  protected $casts = [
    'institution_id' => 'integer',
    'sender_user_id' => 'integer',
    'recipient_category' => MessageRecipientCategory::class,
    'status' => MessageStatus::class,
    'channel' => NotificationChannelsType::class,
    'meta' => 'array'
  ];
  protected $guarded = [];

  function isSent(): bool
  {
    return $this->status === MessageStatus::Sent;
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function sender()
  {
    return $this->belongsTo(User::class, 'sender_user_id');
  }

  function recipient()
  {
    return $this->morphTo('recipient');
  }

  function messageRecipients()
  {
    return $this->hasMany(MessageRecipient::class);
  }

  // SchoolNotification
  function messageable()
  {
    return $this->morphTo('messageable');
  }
}
