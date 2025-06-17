<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

  'sms-charge' => env('SMS_CHARGE', 7),
  'email-charge' => env('EMAIL_CHARGE', 3),

  'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net')
  ],

  'postmark' => [
    'token' => env('POSTMARK_TOKEN')
  ],

  'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1')
  ],

  'pdf' => [
    'url' => env('VITE_PDF_URL')
  ],

  'jwt' => [
    'secret-key' => env('JWT_SECRET_KEY')
  ],

  'paystack' => [
    'private-key' => env('PAYSTACK_PRIVATE_KEY'),
    'public-key' => env('PAYSTACK_PUBLIC_KEY')
  ],

  'bulksms_nigeria' => [
    'api-token' => env('BULKSMS_NIGERIA_API_TOKEN')
  ],

  'ai_keys' => [
    'gemini-api-key' => env('GEMINI_API_KEY')
  ]
];
