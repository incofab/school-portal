<?php
namespace App\Actions\Messages;

use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Models\Association;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Message;
use App\Models\Institution;
use App\Models\User;
use DB;
use Illuminate\Database\Eloquent\Model;

class RecordMessage
{
  private array $recipient = [];
  /**
   * @param array {
   *   subject: string,
   *   body: string,
   *   channel: string,
   * } $post
   */
  function __construct(
    private Institution $institution,
    private User $senderUser,
    private array $post
  ) {
  }

  private function forClass(Classification $classification)
  {
    $this->recipient = [
      'recipient_type' => $classification->getMorphClass(),
      'recipient_id' => $classification->id
    ];
    $this->post['recipient_category'] =
      MessageRecipientCategory::Classification->value;
  }

  private function forClassGroup(ClassificationGroup $classificationGroup)
  {
    $this->recipient = [
      'recipient_type' => $classificationGroup->getMorphClass(),
      'recipient_id' => $classificationGroup->id
    ];
    $this->post['recipient_category'] =
      MessageRecipientCategory::ClassificationGroup->value;
    return $this;
  }

  private function forAssociation(Association $association)
  {
    $this->recipient = [
      'recipient_type' => $association->getMorphClass(),
      'recipient_id' => $association->id
    ];
    $this->post['recipient_category'] =
      MessageRecipientCategory::Association->value;
    return $this;
  }

  private function forInstitution(Institution $institution)
  {
    $this->recipient = [
      'recipient_type' => $institution->getMorphClass(),
      'recipient_id' => $institution->id
    ];
    $this->post['recipient_category'] =
      MessageRecipientCategory::ClassificationGroup->value;
    return $this;
  }

  private function forUser(User $user)
  {
    $this->recipient = [
      'recipient_type' => $user->getMorphClass(),
      'recipient_id' => $user->id
    ];
    $this->post['recipient_category'] = MessageRecipientCategory::Single->value;
    return $this;
  }

  function forModel(?Model $model)
  {
    if ($model instanceof Classification) {
      $this->forClass($model);
    } elseif ($model instanceof ClassificationGroup) {
      $this->forClassGroup($model);
    } elseif ($model instanceof Institution) {
      $this->forInstitution($model);
    } elseif ($model instanceof Association) {
      $this->forAssociation($model);
    } elseif ($model instanceof User) {
      $this->forUser($model);
    }
    return $this;
  }

  public function forSingle(string $contact)
  {
    $this->recipient = ['recipient_contact' => $contact];
    $this->post['recipient_category'] = MessageRecipientCategory::Single->value;
    return $this;
  }

  public function forMultiple(array $contacts)
  {
    $this->recipient['recipient_contact'] = implode(',', $contacts);
    $this->post['recipient_category'] =
      MessageRecipientCategory::Multiple->value;
    return $this;
  }

  public function save(?Model $messageable = null): Message
  {
    DB::beginTransaction();
    /** @var Message $message */
    $message = Message::create([
      ...$this->post,
      'institution_id' => $this->institution->id,
      'sender_user_id' => $this->senderUser->id,
      'status' => MessageStatus::Pending->value,
      'messageable_type' => $messageable?->getMorphClass(),
      'messageable_id' => $messageable?->id
    ]);
    $message
      ->messageRecipients()
      ->create([
        ...$this->recipient,
        'institution_id' => $this->institution->id
      ]);
    DB::commit();
    return $message;
  }
}
