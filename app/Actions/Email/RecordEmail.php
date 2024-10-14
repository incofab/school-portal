<?php
namespace App\Actions\Email;

use App\Enums\EmailRecipientType;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Email;
use App\Models\Institution;
use App\Models\User;
use DB;

class RecordEmail
{
  private array $recipient = [];
  /**
   * @param array{
   *   subject: string,
   *   body: string,
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
    $this->post['type'] = EmailRecipientType::Classification->value;
  }

  public function forClassGroup(ClassificationGroup $classificationGroup)
  {
    $this->recipient = [
      'recipient_type' => $classificationGroup->getMorphClass(),
      'recipient_id' => $classificationGroup->id
    ];
    $this->post['type'] = EmailRecipientType::ClassificationGroup->value;
  }

  public function forInstitution(Institution $institution)
  {
    $this->recipient = [
      'recipient_type' => $institution->getMorphClass(),
      'recipient_id' => $institution->id
    ];
    $this->post['type'] = EmailRecipientType::ClassificationGroup->value;
  }

  public function forSingle(string $email)
  {
    $this->recipient = ['recipient_email' => $email];
    $this->post['type'] = EmailRecipientType::Single->value;
  }

  public function forMultiple(array $emails)
  {
    $this->recipient['recipient_email'] = implode(',', $emails);
    $this->post['type'] = EmailRecipientType::Single->value;
  }

  public function save()
  {
    DB::beginTransaction();
    Email::create($this->post);
    $email = Email::create([
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
