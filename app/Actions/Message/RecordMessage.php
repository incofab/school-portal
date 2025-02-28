<?php
namespace App\Actions\Message;

use App\Enums\MessageRecipientCategory;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Message;
use App\Models\Institution;
use App\Models\User;
use DB;

class RecordMessage
{
  private array $recipient = [];
  /**
   * @param array{
   *   subject: string,
   *   body: string,
   *   channel: string,
   * }
   */
  function __construct(
    private Institution $institution,
    private User $senderUser,
    private array $post
  ) {
  }

  public function forClass(Classification $classification)
  {
    $this->recipient = [
      'recipient_type' => $classification->getMorphClass(),
      'recipient_id' => $classification->id
    ];
    $this->post['recipient_category'] =
      MessageRecipientCategory::Classification->value;
  }

  public function forClassGroup(ClassificationGroup $classificationGroup)
  {
    $this->recipient = [
      'recipient_type' => $classificationGroup->getMorphClass(),
      'recipient_id' => $classificationGroup->id
    ];
    $this->post['recipient_category'] =
      MessageRecipientCategory::ClassificationGroup->value;
  }

  public function forInstitution(Institution $institution)
  {
    $this->recipient = [
      'recipient_type' => $institution->getMorphClass(),
      'recipient_id' => $institution->id
    ];
    $this->post['recipient_category'] =
      MessageRecipientCategory::ClassificationGroup->value;
  }

  public function forSingle(string $email)
  {
    $this->recipient = ['recipient_contact' => $email];
    $this->post['recipient_category'] = MessageRecipientCategory::Single->value;
  }

  public function forMultiple(array $emails)
  {
    $this->recipient['recipient_contact'] = implode(',', $emails);
    $this->post['recipient_category'] = MessageRecipientCategory::Single->value;
  }

  public function save()
  {
    DB::beginTransaction();
    Message::create($this->post);
    $email = Message::create([
      ...$this->post,
      'institution_id' => $this->institution->id,
      'sender_user_id' => $this->senderUser->id
    ]);
    $email
      ->recipients()
      ->create([
        ...$this->recipient,
        'institution_id' => $this->institution->id
      ]);
    DB::commit();
  }
}
