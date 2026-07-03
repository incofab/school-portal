<?php
namespace App\Services\Messaging\Whatsapp\Templates;

use App\Models\TermResult;
use App\Services\Messaging\Whatsapp\PhoneNumberNormalizer;

class WhatsappTemplateResult extends WhatsappTemplate
{
  function __construct(
    private string $schoolName,
    private string $receiverPhoneNumber,
    private string $receiverName,
    private string $studentName,
    private string $term,
    private string $academicSession,
    private string $resultLink
  ) {
    parent::__construct('student_result', $receiverPhoneNumber);
  }

  static function fromTermResult(
    TermResult $termResult
  ): ?WhatsappTemplateResult {
    $student = $termResult->student;
    $user = $student?->user;
    $guardian = $student?->guardian;
    $session = $termResult->academicSession?->title ?? '';

    $term =
      ucfirst($termResult->term->value ?? '') .
      ($termResult->for_mid_term ? ' Mid-Term' : ' Term');

    $phone = $student->guardian_phone ?? ($guardian?->phone ?? $user?->phone);
    if (!$phone) {
      return null;
    }

    return new self(
      schoolName: $termResult->institution?->name ?? '',
      receiverPhoneNumber: $phone,
      receiverName: $guardian?->full_name ?? ($user?->full_name ?? ''),
      studentName: $user?->full_name ?? '',
      term: $term,
      academicSession: $session,
      resultLink: $termResult->signedUrl()
    );
  }

  function payload(): array
  {
    return [
      'messaging_product' => 'whatsapp',
      'to' => (new PhoneNumberNormalizer())->normalize(
        $this->receiverPhoneNumber
      ),
      'type' => 'template',
      'template' => [
        'name' => $this->getTemplateName(),
        'language' => ['code' => 'en'],
        'components' => [
          [
            'type' => 'header',
            'parameters' => [
              [
                'type' => 'text',
                'text' => $this->schoolName,
                'parameter_name' => 'school_name'
              ]
            ]
          ],
          [
            'type' => 'body',
            'parameters' => [
              [
                'type' => 'text',
                'text' => $this->receiverName,
                'parameter_name' => 'receiver_name'
              ],
              [
                'type' => 'text',
                'text' => $this->studentName,
                'parameter_name' => 'student_name'
              ],
              [
                'type' => 'text',
                'text' => $this->term,
                'parameter_name' => 'term'
              ],
              [
                'type' => 'text',
                'text' => "{$this->academicSession} Session",
                'parameter_name' => 'academic_session'
              ],
              [
                'type' => 'text',
                'text' => $this->resultLink,
                'parameter_name' => 'result_link'
              ]
            ]
          ]
        ]
      ]
    ];
  }
}
