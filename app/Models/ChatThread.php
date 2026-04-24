<?php

namespace App\Models;

use App\Enums\ChatThreadType;
use App\Enums\InstitutionUserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChatThread extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'requester_user_id' => 'integer',
    'target_user_id' => 'integer',
    'type' => ChatThreadType::class,
    'last_message_at' => 'datetime'
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function requester()
  {
    return $this->belongsTo(User::class, 'requester_user_id');
  }

  public function targetUser()
  {
    return $this->belongsTo(User::class, 'target_user_id');
  }

  public function messages()
  {
    return $this->hasMany(ChatMessage::class);
  }

  public function latestMessage()
  {
    return $this->hasOne(ChatMessage::class)->latestOfMany();
  }

  public function reads()
  {
    return $this->hasMany(ChatThreadRead::class);
  }

  public function scopeVisibleTo(
    $query,
    Institution $institution,
    User $user,
    InstitutionUser $institutionUser
  ) {
    return $query
      ->where('institution_id', $institution->id)
      ->where(function ($query) use ($user, $institutionUser) {
        $query
          ->where('requester_user_id', $user->id)
          ->orWhere(function ($query) use ($user) {
            $query
              ->where('type', ChatThreadType::DirectUser->value)
              ->where('target_user_id', $user->id);
          });

        if ($institutionUser->isAdmin()) {
          $query
            ->orWhere('type', ChatThreadType::Institution->value)
            ->orWhere('type', ChatThreadType::Role->value);

          return;
        }

        if (
          in_array($institutionUser->role, [
            InstitutionUserType::Teacher,
            InstitutionUserType::Accountant
          ])
        ) {
          $query->orWhere(function ($query) use ($institutionUser) {
            $query
              ->where('type', ChatThreadType::Role->value)
              ->where('target_role', $institutionUser->role->value);
          });
        }
      });
  }

  public function canBeAccessedBy(
    User $user,
    InstitutionUser $institutionUser
  ): bool {
    if ($this->requester_user_id === $user->id) {
      return true;
    }

    if ($this->type === ChatThreadType::DirectUser) {
      return $this->target_user_id === $user->id;
    }

    if ($institutionUser->isAdmin()) {
      return true;
    }

    if ($this->type === ChatThreadType::Institution) {
      return false;
    }

    return $this->target_role === $institutionUser->role->value;
  }

  public function markAsRead(User $user, ?ChatMessage $message = null): void
  {
    $message = $message ?? $this->latestMessage;

    if (!$message) {
      return;
    }

    $this->reads()->updateOrCreate(
      ['user_id' => $user->id],
      [
        'institution_id' => $this->institution_id,
        'last_read_chat_message_id' => $message->id,
        'read_at' => now()
      ]
    );
  }

  public function recordLastMessage(ChatMessage $message): void
  {
    $this->fill([
      'last_message_at' => $message->created_at,
      'last_message_preview' => str($message->body)->limit(160)->value()
    ])->save();
  }

  public static function unreadCountFor(
    Institution $institution,
    User $user,
    InstitutionUser $institutionUser
  ): int {
    return static::query()
      ->select('chat_threads.id')
      ->visibleTo($institution, $user, $institutionUser)
      ->whereExists(function ($query) use ($user) {
        $query
          ->select(DB::raw(1))
          ->from('chat_messages as latest_chat_message')
          ->whereColumn('latest_chat_message.chat_thread_id', 'chat_threads.id')
          ->whereRaw(
            'latest_chat_message.id = (
              select max(chat_messages.id)
              from chat_messages
              where chat_messages.chat_thread_id = chat_threads.id
            )'
          )
          ->where('latest_chat_message.sender_user_id', '!=', $user->id)
          ->whereRaw(
            'latest_chat_message.id > coalesce((
              select chat_thread_reads.last_read_chat_message_id
              from chat_thread_reads
              where chat_thread_reads.chat_thread_id = chat_threads.id
                and chat_thread_reads.user_id = ?
              limit 1
            ), 0)',
            [$user->id]
          );
      })
      ->count();
  }
}
