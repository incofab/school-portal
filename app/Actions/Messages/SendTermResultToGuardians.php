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

    // (new \App\Services\Messaging\Whatsapp\WhatsappClient($contentData))->send();
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

  private function buildTemplateComponents(TermResult $termResult): array|null
  {
    $student = $termResult->student;
    $user = $student?->user;
    $session = $termResult->academicSession?->title ?? '';
    $term = $termResult->for_mid_term
      ? 'Mid-'
      : '' . ucfirst($termResult->term->value ?? '');
    // $url = $termResult->signedUrl();
    $phone = $student->guardian_phone ?? $student->guardian?->phone;
    // $phone = '07036098561';
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
          'code' => 'en'
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
                'text' => $termResult->signedUrl()
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
                'text' => trim($termResult->signedUrl(false), '/')
              ]
            ]
          ]
        ]
      ]
    ];

    return $body;
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
