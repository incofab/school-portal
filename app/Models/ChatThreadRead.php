<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatThreadRead extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'chat_thread_id' => 'integer',
    'user_id' => 'integer',
    'last_read_chat_message_id' => 'integer',
    'read_at' => 'datetime'
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function thread()
  {
    return $this->belongsTo(ChatThread::class, 'chat_thread_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function lastReadMessage()
  {
    return $this->belongsTo(ChatMessage::class, 'last_read_chat_message_id');
  }
}
