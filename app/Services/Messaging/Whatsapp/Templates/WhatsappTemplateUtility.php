<?php
namespace App\Services\Messaging\Whatsapp\Templates;

use App\Services\Messaging\Whatsapp\PhoneNumberNormalizer;

class WhatsappTemplateUtility extends WhatsappTemplate
{
  function __construct(
    private string $receiverPhoneNumber,
    private string $schoolName,
    private string $receiverName,
    private string $message
  ) {
    parent::__construct('utility_message', $receiverPhoneNumber);
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
                'parameter_name' => 'name'
              ],
              [
                'type' => 'text',
                'text' => $this->message,
                'parameter_name' => 'message'
              ]
            ]
          ]
        ]
      ]
    ];
  }
}
