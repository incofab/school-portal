<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class DummyController extends Controller
{
  public function __construct()
  {
  }

  function sendWhatsappMessage()
  {
    // info('Sending WhatsApp message... 1');
    // dd('dks fkksm dksmdsl rkemrelfs:   ');
    $response = Http::withToken(
      config('services.facebook.whatsapp-access-token')
    )
      ->withHeaders(['Content-Type' => 'application/json'])
      ->post('https://graph.facebook.com/v22.0/819996257873340/messages', [
        'messaging_product' => 'whatsapp',
        'to' => '2347036098561', // recipient phone number in international format
        'type' => 'template',
        'template' => [
          'name' => 'hello_world', // your template name
          'language' => [
            'code' => 'en_US'
          ]
        ]
        // 'type' => 'text',
        // 'text' => [
        //   'body' => 'Hello, this is a fourth test message from Edumanager!'
        // ]
      ]);
    info('Sending WhatsApp message... 2');

    info($response->body());
    info('Sending WhatsApp message... 3');

    return $response->json();
  }
}
