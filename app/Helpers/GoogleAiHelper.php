<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class GoogleAiHelper
{
  public static function ask($question, $model = 'gemini-2.0-flash')
  {
    $geminiApiKey = config('services.ai_keys.gemini-api-key');

    $response = Http::withHeaders([
      'Content-Type' => 'application/json'
    ])->post(
      "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiApiKey}",
      [
        'contents' => [
          [
            'parts' => [
              [
                'text' => $question
              ]
            ]
          ]
        ]
      ]
    );

    return $response->json();
  }
}
