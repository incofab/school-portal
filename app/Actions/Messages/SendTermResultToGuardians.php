<?php
namespace App\Actions\Messages;

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Jobs\SendWhatsappTemplateMessage;
use App\Models\Institution;
use App\Models\TermResult;
use App\Models\User;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplate;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplateResult;
use Illuminate\Support\Collection;

class SendTermResultToGuardians
{
  public function __construct(
    private Institution $institution,
    private User $senderUser
  ) {
  }

  /**
   * @param Collection<int, TermResult> $termResults
   */
  function multiSend($termResults)
  {
    $templates = [];
    $contacts = [];
    foreach ($termResults as $termResult) {
      $termResult->loadMissing(
        'student.user',
        'student.guardian',
        'academicSession'
      );
      if (!$termResult->isActivated() || !$termResult->isPublished()) {
        return failRes('Result is not ready to be shared');
      }

      $template = WhatsappTemplateResult::fromTermResult($termResult);
      if (!$template) {
        continue;
      }
      $templates[] = $template;
      $contacts[] = $template->getReceiverPhoneNumber();
    }
    return $this->send($templates, $contacts);
  }

  /**
   * @param WhatsappTemplate[] $templates
   * @param array $contacts
   */
  function send(array $templates, array $contacts)
  {
    if (empty($templates) || empty($contacts)) {
      return failRes(
        'No guardian WhatsApp contact found for the selected results'
      );
    }

    $record = new RecordMessage($this->institution, $this->senderUser, [
      'subject' => 'Term Result',
      'body' => 'Students term results.',
      'channel' => NotificationChannelsType::Whatsapp->value,
      'status' => MessageStatus::Pending->value
    ]);

    $messageModel =
      count($contacts) > 1
        ? $record->forMultiple($contacts)->save()
        : $record->forSingle($contacts[0])->save();

    /* We won't be applying charges for now */
    // $res = ApplyMessageCharges::make($this->institution)->run(
    //   collect($contacts),
    //   NotificationChannelsType::Whatsapp->value,
    //   $messageModel
    // );
    // if ($res->isNotSuccessful()) {
    //   return $res;
    // }

    foreach ($templates as $key => $template) {
      SendWhatsappTemplateMessage::dispatch($template, $messageModel);
    }

    return successRes('Results sent successfully');
  }

  static function test()
  {
    $termResult = TermResult::query()
      ->with(
        'institution.createdBy',
        'student.user',
        'student.guardian',
        'academicSession'
      )
      ->first();
    return (new SendTermResultToGuardians(
      $termResult->institution,
      $termResult->createdBy ?? User::first()
    ))->multiSend(collect([$termResult]));
  }
}
