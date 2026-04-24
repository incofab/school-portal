<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'chat_thread_id' => 'integer',
    'sender_user_id' => 'integer'
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function thread()
  {
    return $this->belongsTo(ChatThread::class, 'chat_thread_id');
  }

  public function sender()
  {
    return $this->belongsTo(User::class, 'sender_user_id');
  }
}
