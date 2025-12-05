<?php
namespace App\Actions\Messages;

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\TermResult;
use App\Models\User;
use App\Services\Messaging\MessageDispatcher;
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
    $contentData = [];
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
      $content = $this->buildTemplateComponents($termResult);
      if (!$content) {
        continue;
      }
      $contentData[] = $content;
      $contacts[] = $content['to'];
    }
    return $this->send($contentData, $contacts);
  }

  function send(array $contentData, array $contacts)
  {
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

    $res = ApplyMessageCharges::make($this->institution)->run(
      collect($contacts),
      NotificationChannelsType::Whatsapp->value,
      $messageModel
    );
    if ($res->isNotSuccessful()) {
      return $res;
    }

    $dispatcher = new MessageDispatcher($this->institution);
    $dispatcher->dispatch(
      receivers: collect($contacts),
      channel: NotificationChannelsType::Whatsapp,
      message: 'Students term results.',
      subject: 'Term Result',
      messageModel: $messageModel,
      context: $contentData
    );
  }

  /* @deprecated 
  public function viaWhatsapp(TermResult $termResult): Res
  {
    $termResult->loadMissing(
      'student.user',
      'student.guardian',
      'academicSession'
    );

    if (!$termResult->isActivated() || !$termResult->isPublished()) {
      return failRes('Result is not ready to be shared');
    }

    $guardians = GuardianStudent::query()
      ->where('student_id', $termResult->student_id)
      ->with('guardian')
      ->get()
      ->map(fn(GuardianStudent $item) => $item->guardian)
      ->filter(fn($guardian) => $guardian?->phone);

    if ($guardians->isEmpty()) {
      return failRes('No guardian phone numbers found for this student');
    }

    $contacts = $guardians
      ->map(fn($guardian) => formatWhatsappNumber($guardian->phone))
      ->filter();

    if ($contacts->isEmpty()) {
      return failRes('No valid WhatsApp numbers found for guardians');
    }

    $body = $this->buildMessage($termResult);

    $record = new RecordMessage($this->institution, $this->senderUser, [
      'subject' => 'Term Result',
      'body' => $body,
      'channel' => NotificationChannelsType::Whatsapp->value,
      'status' => MessageStatus::Pending->value
    ]);

    $messageModel =
      $contacts->count() > 1
        ? $record->forMultiple($contacts->toArray())->save($termResult)
        : $record->forSingle($contacts->first())->save($termResult);

    $res = ApplyMessageCharges::make($this->institution)->run(
      $contacts,
      NotificationChannelsType::Whatsapp->value,
      $messageModel
    );
    if ($res->isNotSuccessful()) {
      return $res;
    }

    $dispatcher = new MessageDispatcher($this->institution);
    $dispatcher->dispatch(
      receivers: $contacts,
      channel: NotificationChannelsType::Whatsapp,
      message: $body,
      subject: 'Term Result',
      messageModel: $messageModel,
      context: [
        'template' => config('services.facebook.whatsapp-template-result'),
        'components' => $this->buildTemplateComponents($termResult)
      ]
    );

    return successRes('WhatsApp delivery queued', [
      'message_id' => $messageModel->id
    ]);
  }
  */

  private function buildTemplateComponents(TermResult $termResult): array|null
  {
    $student = $termResult->student;
    $user = $student?->user;
    $session = $termResult->academicSession?->title ?? '';
    $term = $termResult->for_mid_term
      ? 'Mid-'
      : '' . ucfirst($termResult->term->value ?? '');
    $url = $termResult->signedUrl();
    $phone = $student->guardian_phone ?? $student->guardian?->phone;
    if (!$phone) {
      return null;
    }

    $body = [
      'messaging_product' => 'whatsapp',
      'to' => formatWhatsappNumber($phone),
      'type' => 'template',
      'template' => [
        'name' => 'result_published', // your template name
        'language' => [
          'code' => 'en_US'
        ],
        'components' => [
          [
            'type' => 'header',
            'parameters' => [
              [
                'type' => 'text',
                'parameter_name' => 'school_name',
                'text' => $this->institution->name
              ]
            ]
          ],
          [
            'type' => 'body',
            'parameters' => [
              [
                'type' => 'text',
                'parameter_name' => 'student_name',
                'text' => $user?->full_name ?? 'Student'
              ],
              [
                'type' => 'text',
                'parameter_name' => 'result_title',
                'text' => "{$session} {$term} Term Result"
              ],
              [
                'type' => 'text',
                'parameter_name' => 'result_link',
                'text' => $url
              ]
            ]
          ],
          [
            'type' => 'button',
            'sub_type' => 'url',
            'index' => '0',
            'parameters' => [
              [
                'type' => 'text',
                'parameter_name' => '1',
                'text' => $url
              ]
            ]
          ]
        ]
      ]
    ];

    return $body;
  }
}
