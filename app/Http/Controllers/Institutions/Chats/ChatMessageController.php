<?php

namespace App\Http\Controllers\Institutions\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\Institution;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
  public function store(
    Request $request,
    Institution $institution,
    ChatThread $chatThread
  ) {
    abort_unless(
      $chatThread->institution_id === $institution->id,
      404,
      'Chat thread not found'
    );

    abort_unless(
      $chatThread->canBeAccessedBy(currentUser(), currentInstitutionUser()),
      403,
      'You are not allowed to reply to this chat thread.'
    );

    $data = $request->validate([
      'message' => ['required', 'string', 'max:2000']
    ]);

    $message = $chatThread->messages()->create([
      'institution_id' => $institution->id,
      'sender_user_id' => currentUser()->id,
      'body' => $data['message']
    ]);

    $chatThread->recordLastMessage($message);
    $chatThread->markAsRead(currentUser(), $message);

    return $this->ok([
      'thread_id' => $chatThread->id,
      'message' => 'Message sent successfully.'
    ]);
  }
}
