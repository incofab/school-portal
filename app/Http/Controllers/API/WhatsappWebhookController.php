<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Messaging\Whatsapp\WhatsappWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
  public function verify(Request $request)
  {
    $mode = $request->query('hub_mode', $request->query('hub.mode'));
    $token = $request->query(
      'hub_verify_token',
      $request->query('hub.verify_token')
    );
    $challenge = $request->query(
      'hub_challenge',
      $request->query('hub.challenge')
    );

    if (
      $mode === 'subscribe' &&
      filled($challenge) &&
      hash_equals(
        (string) config('services.facebook.whatsapp-webhook-verify-token'),
        (string) $token
      )
    ) {
      return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    return response('Forbidden', 403);
  }

  public function receive(Request $request, WhatsappWebhookHandler $handler)
  {
    $payload = $request->all();

    Log::info('WhatsApp webhook received.', [
      'entries' => count($payload['entry'] ?? [])
    ]);

    try {
      $handler->handle($payload);
    } catch (\Throwable $exception) {
      Log::error('WhatsApp webhook handling failed.', [
        'message' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString()
      ]);
    }

    return response()->json(['status' => 'ok']);
  }
}
