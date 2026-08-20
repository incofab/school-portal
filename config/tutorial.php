<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Tutorial demo user credentials
  |--------------------------------------------------------------------------
  |
  | Used only by `php artisan tutorial:seed-demo-user` and the Playwright
  | tutorial video generator (tutorials/). Never point these at a real
  | account.
  |
  */

  'demo_email' => env('TUTORIAL_USER_EMAIL', 'tutorial@example.com'),
  'demo_password' => env('TUTORIAL_USER_PASSWORD', 'password')
];
