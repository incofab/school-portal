<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Audit Retention Windows
  |--------------------------------------------------------------------------
  |
  | Activity logs are grouped into retention categories so routine activity can
  | be pruned sooner while security and money-related events are kept longer.
  | The audit:prune command reads these values and deletes only records older
  | than the configured number of days for their retention_category.
  |
  | normal: routine academic/admin changes.
  | security: auth, authorization, impersonation, and critical/security events.
  | financial: fee, payment, wallet, payroll, and expense events.
  |
  */
  'retention_days' => [
    'normal' => (int) env('AUDIT_RETENTION_NORMAL_DAYS', 365),
    'security' => (int) env('AUDIT_RETENTION_SECURITY_DAYS', 1095),
    'financial' => (int) env('AUDIT_RETENTION_FINANCIAL_DAYS', 2555)
  ],

  /*
  |--------------------------------------------------------------------------
  | Audit Integrity Hashing
  |--------------------------------------------------------------------------
  |
  | When enabled, each audit row stores a row_hash calculated from its important
  | fields and a previous_hash pointing to the prior audit row. This creates a
  | lightweight hash chain: if an old row is edited directly in the database,
  | ActivityLog::verifyIntegrity() and ActivityLog::verifyChain() can detect it.
  |
  | The secret is the HMAC key used to calculate row hashes. APP_KEY is a safe
  | default for local installs, but production can set AUDIT_INTEGRITY_SECRET to
  | a dedicated long random value so audit verification does not depend on the
  | application encryption key.
  |
  */
  'integrity' => [
    'enabled' => env('AUDIT_INTEGRITY_ENABLED', true),
    'secret' => env('AUDIT_INTEGRITY_SECRET', env('APP_KEY'))
  ]
];
