<?php

namespace App\Models;

use App\Enums\SchoolNotificationPurpose;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolNotification extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'sender_user_id' => 'integer',
    'receiver_ids' => AsArrayObject::class,
    'purpose' => SchoolNotificationPurpose::class
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function sender()
  {
    return $this->belongsTo(User::class, 'sender_user_id');
  }
  function messages()
  {
    return $this->morphMany(Message::class, 'messageable');
  }
}
