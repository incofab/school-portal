<?php

return [
  'retention_days' => [
    'normal' => (int) env('AUDIT_RETENTION_NORMAL_DAYS', 365),
    'security' => (int) env('AUDIT_RETENTION_SECURITY_DAYS', 1095),
    'financial' => (int) env('AUDIT_RETENTION_FINANCIAL_DAYS', 2555)
  ],

  'integrity' => [
    'enabled' => env('AUDIT_INTEGRITY_ENABLED', true),
    'secret' => env('AUDIT_INTEGRITY_SECRET', env('APP_KEY'))
  ]
];
