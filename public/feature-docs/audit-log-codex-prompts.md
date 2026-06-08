# Audit Log Implementation Prompts For Codex CLI

This document breaks the EduManager audit/activity logging feature into independent stages. Each stage contains a prompt that can be supplied directly to Codex CLI.

General rule for every stage: before editing, Codex must read `public/feature-docs/features.html` and then inspect the specific backend/frontend/data documentation relevant to the files it will touch. Every behavioral change must update `public/feature-docs/features.html` and any affected `backend.html`, `frontend.html`, and `data.html`.

## Stage 1: Audit Log Foundation [DONE]

Use this prompt to create the core database model, service, context capture, permissions, and tests. This stage should avoid integrating every application feature. It should create the reusable foundation only.

````markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement the foundation for a standard audit/activity logging system.

Before making changes:

- Read `public/feature-docs/features.html`.
- Read `public/feature-docs/backend.html`, `public/feature-docs/data.html`, and `public/feature-docs/frontend.html` only as needed.
- Study existing project conventions for migrations, models, actions/services, policies/permissions, middleware, routes, Pest tests, and Inertia pages.

Goal:
Create a reusable audit log foundation that can later be used by all feature areas. Do not try to log every feature yet. Build the central infrastructure cleanly.

Required backend work:

- Add an `activity_logs` table.
- Add an `ActivityLog` model.
- Add a reusable `ActivityLogger` service or support class under an appropriate namespace, following the project's existing structure.
- Add a request context mechanism so audit logs can automatically capture request details when available.
- Add permissions/authorization for viewing audit logs.
- Add manager-level routes/controllers for listing audit logs.
- Add institution-level routes/controllers for listing audit logs scoped to the current institution.
- Add basic pagination, search, and filtering support at backend level.

The `activity_logs` table should support at least:

- `id`
- `uuid`
- `institution_id` nullable
- `institution_group_id` nullable
- `actor_type` nullable
- `actor_id` nullable
- `actor_name` nullable snapshot
- `actor_role` nullable snapshot
- `actor_guard` nullable
- `action`
- `category`
- `event`
- `subject_type` nullable
- `subject_id` nullable
- `subject_name` nullable snapshot
- `description` nullable
- `properties` JSON nullable
- `old_values` JSON nullable
- `new_values` JSON nullable
- `ip_address` nullable
- `user_agent` nullable
- `route_name` nullable
- `url` nullable
- `method` nullable
- `request_id` nullable
- `impersonator_type` nullable
- `impersonator_id` nullable
- `severity` default `info`
- timestamps

Design requirements:

- Make the logger safe to call from HTTP requests, queued jobs, scheduled commands, and tests.
- The logger must work when there is no authenticated actor.
- The logger must support explicit actor, subject, institution, category, event, description, severity, properties, old values, and new values.
- Avoid logging secrets. Add a central list of sensitive keys that should be redacted from JSON payloads and model diffs.
- Store actor and subject display-name snapshots when possible.
- Keep the implementation framework-native and consistent with existing project style.
- Do not introduce an external audit package unless you have a strong reason. Prefer a custom foundation.

Suggested logger API shape:

```php
app(ActivityLogger::class)
  ->event('audit.test_event')
  ->category('system')
  ->action('created')
  ->by($user)
  ->on($subject)
  ->inInstitution($institution)
  ->description('Created a test audit event')
  ->properties(['key' => 'value'])
  ->severity('info')
  ->log();
```

Frontend work:

- Add simple manager and institution audit log list pages using existing Inertia + React/TypeScript + Chakra UI conventions.
- Include filters for date range, category, event, actor, subject, severity, and institution where appropriate.
- Include a detail view or modal/drawer that shows actor, subject, description, request context, properties, old values, and new values.
- Keep the UI practical and consistent with existing admin list pages.

Tests:

- Add focused Pest feature/unit tests proving:
  - An activity log can be created with the service.
  - Guest/system activity can be logged without an actor.
  - Request context is captured during an HTTP request.
  - Sensitive keys are redacted.
  - Manager/global users can view logs according to existing authorization rules.
  - Institution admins can only view logs for their institution.
  - Cross-institution log access is blocked.

Documentation:

- Update `public/feature-docs/features.html` to add a new feature area for audit logs/activity monitoring.
- Update `public/feature-docs/backend.html` for new routes/controllers/services/middleware.
- Update `public/feature-docs/frontend.html` for new audit log pages/components.
- Update `public/feature-docs/data.html` for the `activity_logs` table/model.

Verification:

- Run the most relevant Pest tests.
- If frontend files are changed, run the relevant lint/typecheck command if available.
- Report changed files and test results.
````

## Stage 2: Automatic Model Auditing [DONE]

Use this prompt after Stage 1 exists, or use it independently by first building any missing minimal foundation. This stage should add automatic auditing for core model create/update/delete events.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement automatic model auditing for high-value domain models.

Before making changes:

- Read `public/feature-docs/features.html`.
- Inspect the existing audit log foundation if present: `ActivityLog`, `ActivityLogger`, audit routes/controllers/pages, and related tests.
- If the audit foundation does not exist, stop and implement only the minimal foundation needed for this stage, then continue.
- Study existing model, observer, trait, and provider conventions.

Goal:
Automatically record create/update/delete/restore activity for selected models using observers or a reusable trait. Avoid editing every controller.

Target models:

- `Institution`
- `InstitutionGroup`
- `User`
- `InstitutionUser`
- `Student`
- `GuardianStudent`
- `Classification`
- `ClassificationGroup`
- `ClassDivision`
- `Course`
- `CourseTeacher`
- `CourseSession`
- `Topic`
- `LessonPlan`
- `LessonNote`
- `Assignment`
- `AssignmentSubmission`
- Core result-related models found in the feature docs
- Core payment/wallet/withdrawal models found in the feature docs
- Admission application/form models found in the feature docs
- `Media`

Required work:

- Add a reusable model-auditing trait, observer, or registry that maps model lifecycle events to audit log records.
- Capture `created`, `updated`, `deleted`, `restored`, and `force_deleted` where supported.
- Capture old/new values for changed fields on updates.
- Redact sensitive fields using the central redaction rules from the audit foundation.
- Add readable subject names for common models.
- Avoid logging noisy timestamp-only changes.
- Avoid duplicate logs when a domain-specific event will be more meaningful, where this is already obvious.
- Make auditing opt-in per model, not a global blanket on every Eloquent model.
- Ensure bulk imports and queued jobs can still use the model audit path without breaking.

Design requirements:

- The implementation must be easy to extend for more models.
- Logs must include institution/institution group context where it can be derived from the model.
- Logs must include the authenticated/request actor when available.
- Logs must still work in CLI/queue context when no request exists.
- Do not log secrets, passwords, tokens, API keys, private payment credentials, or large document/content bodies.

Tests:

- Add Pest tests for at least:
  - Create audit log on model creation.
  - Update audit log with old/new values.
  - Delete audit log.
  - Sensitive fields are redacted.
  - Timestamp-only changes are ignored.
  - Institution scoping is set correctly for institution-owned models.
  - CLI/system context does not crash.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html` for observers/traits/service changes.
- Update `public/feature-docs/data.html` for audited model coverage and audit-log payload shape.

Verification:

- Run focused Pest tests for audit logging and any affected model areas.
- Report changed files and test results.
```

## Stage 3: Authentication, Access, Roles, And Impersonation Audit Events [DONE]

Use this prompt to cover security-sensitive activity. This stage should be able to run independently if the audit foundation exists.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement explicit audit logging for authentication, authorization, role, permission, and impersonation workflows.

Before making changes:

- Read `public/feature-docs/features.html`.
- Inspect the existing auth/access/impersonation feature docs and source files.
- Inspect the existing audit foundation.
- If no audit foundation exists, create the minimal `activity_logs` table/model/service needed to complete this stage.

Goal:
Record all security-sensitive account activity with clear domain event names and useful context.

Required events:

- Staff/admin login success.
- Staff/admin login failure.
- Student login success.
- Student login failure.
- Logout.
- Password reset requested.
- Password reset completed.
- Password changed by user.
- Password reset by admin/manager.
- User profile/security identity updates such as BVN/NIN where present.
- User created.
- User status changed.
- User deleted.
- Role changed.
- Permission changed, if the app has direct permission changes.
- Manager impersonation started.
- Manager impersonation stopped.
- Admin user-list impersonation started.
- Guardian/student impersonation started and stopped where present.
- Unauthorized access attempts where the app already has clear interception points.

Required event naming:

- Use stable names such as:
  - `auth.login_succeeded`
  - `auth.login_failed`
  - `auth.logout`
  - `auth.password_reset_requested`
  - `auth.password_reset_completed`
  - `auth.password_changed`
  - `access.user_status_changed`
  - `access.role_changed`
  - `access.permission_changed`
  - `access.impersonation_started`
  - `access.impersonation_stopped`

Design requirements:

- Security events should use severity `security`, `warning`, or `critical` where appropriate.
- Failed login logs must not expose passwords.
- Include actor, impersonator, target user/student, institution, IP, user agent, route, and request ID where available.
- When an impersonated session performs an action, future logs should include impersonator context if the project stores that in session.
- Keep logging code out of views. Prefer controllers, actions, middleware, listeners, or services.
- Do not break existing login, logout, password reset, student login, or impersonation behavior.

Frontend work:

- If the audit log detail UI exists, make sure security events display clearly.
- Add or adjust filters so security events can be filtered by category/severity.
- Avoid building a separate security dashboard unless it is clearly small and consistent with existing manager pages.

Tests:

- Add Pest tests for:
  - Successful login logs.
  - Failed login logs without password leakage.
  - Logout logs.
  - Role change logs.
  - User status change logs.
  - Impersonation start/stop logs.
  - Institution scoping and impersonator context.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html`.
- Update `public/feature-docs/frontend.html` only if UI changed.
- Update `public/feature-docs/data.html` if event schema or stored properties changed.

Verification:

- Run focused auth/access/impersonation Pest tests and audit tests.
- Report changed files and test results.
```

## Stage 4: Student, Guardian, Class, Course, Curriculum, Assignment, And Attendance Events [DONE]

Use this prompt to cover academic and classroom operations. It should log meaningful workflow events in addition to automatic model auditing.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement explicit audit logging for academic operations: students, guardians, classes, courses, curriculum, assignments, and attendance.

Before making changes:

- Read `public/feature-docs/features.html`.
- Inspect the feature sections for students, guardians, classifications, courses, curriculum, assignments, and attendance.
- Inspect existing controllers/actions/tests for those areas.
- Inspect the existing audit foundation.
- If no audit foundation exists, create the minimal foundation needed to complete this stage.

Goal:
Record meaningful academic workflow events with clear event names and enough metadata for institution admins and managers to understand what happened.

Required event areas:

- Student created/updated/deleted, if not already covered by model auditing.
- Student code changed.
- Student class changed.
- Multiple student class changes.
- Student promoted.
- Student migrated.
- Student movement reverted.
- Student bulk upload started/completed/failed where implementation has clear points.
- Guardian recorded.
- Guardian assigned to student.
- Guardian dependent removed.
- Class/classification created/updated/deleted.
- Class migration/promotion workflows.
- Course created/updated/deleted.
- Course teacher assigned/removed.
- Course session created/updated/deleted.
- Question bank content uploaded/imported/exported/generated where present.
- Topic/scheme/lesson plan/lesson note created/updated/deleted.
- AI lesson note generated where present.
- Lesson plan/note attachments uploaded/deleted.
- Assignment created/updated/deleted.
- Assignment submitted.
- Assignment scored.
- Attendance recorded/updated/deleted and attendance report generated/exported where present.

Suggested event names:

- `student.created`
- `student.updated`
- `student.class_changed`
- `student.promoted`
- `student.migrated`
- `student.movement_reverted`
- `guardian.assigned`
- `guardian.dependent_removed`
- `classification.created`
- `classification.updated`
- `course.teacher_assigned`
- `course.teacher_removed`
- `question_bank.imported`
- `question_bank.generated`
- `curriculum.lesson_note_generated`
- `assignment.submitted`
- `assignment.scored`
- `attendance.recorded`
- `attendance.updated`

Design requirements:

- Prefer logging from existing Actions/services for business workflows.
- Use model observers only for simple CRUD.
- Include institution, class, student, guardian, course, teacher, term, academic session, and classification metadata where relevant.
- For bulk operations, avoid one excessive log per row unless the project already needs row-level traceability. Prefer a summary log with counts and attach failures/errors in redacted metadata.
- Keep event payloads compact. Do not store full imported files, full lesson content, or large generated text in audit properties.
- Ensure teachers/staff cannot see cross-institution audit data.

Frontend work:

- Extend audit filters/categories if needed for academic categories.
- Ensure log detail view can show academic metadata without layout issues.

Tests:

- Add focused Pest tests for several high-value workflows:
  - Student class change.
  - Student promotion or migration.
  - Guardian assignment/removal.
  - Course teacher assignment.
  - Assignment scoring.
  - Attendance record/update if there are existing attendance tests.
- Assert logs include actor, institution, event, subject, and key metadata.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html`.
- Update `public/feature-docs/frontend.html` only if UI changed.
- Update `public/feature-docs/data.html` if payload conventions changed.

Verification:

- Run focused Pest tests for the affected feature areas and audit tests.
- Report changed files and test results.
```

## Stage 5: Results, Assessments, Exams, CBT, And Admissions Events

Use this prompt for high-risk academic records and public-facing application/exam workflows.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement explicit audit logging for assessments, result processing, exams/CBT, external exams, and admissions.

Before making changes:

- Read `public/feature-docs/features.html`.
- Inspect the feature sections for assessments, learning evaluations, result recording/processing/locking/publishing, PINs, exams, CBT, external exams, offline mock API, and admissions.
- Inspect existing controllers/actions/jobs/tests for those areas.
- Inspect the existing audit foundation.
- If no audit foundation exists, create the minimal foundation needed to complete this stage.

Goal:
Record high-value academic integrity events so managers and institution admins can trace who changed assessments, results, exams, and admissions.

Required event areas:

- Assessment created/updated/deleted.
- Learning evaluation/comment created/updated/deleted.
- Result score recorded/updated/deleted.
- Result processing started/completed/failed.
- Result locked.
- Result unlocked.
- Result published.
- Result unpublished.
- Result PIN generated/activated/used where applicable.
- Transcript/session result generated or viewed where appropriate.
- Exam/event created/updated/deleted.
- Admission form created/updated/deleted.
- Admission form purchased.
- Admission application submitted.
- Admission application reviewed/approved/rejected.

Design requirements:

- Use severity `notice`, `warning`, `critical`, or `security` for high-impact events such as result publishing, result unlocking, score changes after locking, PIN usage, and admission approvals.
- Include institution, academic session, term, class, course, student/applicant, exam, and actor metadata where relevant.
- Do not store full answers, full result sheets, full admission documents, or large payloads in the audit log. Store IDs, names, counts, status changes, and compact metadata.
- Public/guest workflows must still log with no actor but with request context.
- Ensure logs remain institution scoped for institution admins and globally visible to authorized managers/admins.

Frontend work:

- Extend audit categories/events filters if needed.
- Make sure result/exam/admission events display well in the audit log detail view.

Tests:

- Add focused Pest tests for high-risk workflows:
  - Result score recorded or updated.
  - Result locked/unlocked.
  - Result published/unpublished.
  - CBT exam submitted or scored.
  - Admission application submitted.
  - Admission approval/rejection.
  - Public result/admission/exam event logs do not require an authenticated actor.
- Assert sensitive/large payloads are not stored.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html`.
- Update `public/feature-docs/frontend.html` only if UI changed.
- Update `public/feature-docs/data.html` if payload conventions changed.

Verification:

- Run focused Pest tests for results/exams/admissions and audit tests.
- Report changed files and test results.
```

## Stage 6: Finance, Payments, Wallets, Payroll, Expenses, And External Integrations Events

Use this prompt for financial auditability. This stage should be stricter about severity, redaction, and before/after values.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement explicit audit logging for finance, payments, wallets, commissions, withdrawals, payroll, expenses, bank accounts, and external payment integrations.

Before making changes:

- Read `public/feature-docs/features.html`.
- Inspect the feature sections for fees, payments, receipts, payment notifications, wallets, fundings, transactions, withdrawals, bank accounts, commissions, payroll, salaries, expenses, and external integrations.
- Inspect existing controllers/actions/jobs/webhook handlers/tests for those areas.
- Inspect the existing audit foundation.
- If no audit foundation exists, create the minimal foundation needed to complete this stage.

Goal:
Record financial activity in a traceable and security-conscious way for global managers/admins and institution admins.

Required event areas:

- Fee created/updated/deleted.
- Fee assigned to class/student/group.
- Payment initiated.
- Payment completed.
- Payment failed.
- Manual payment proof uploaded.
- Manual payment approved.
- Manual payment rejected.
- Receipt generated/viewed/downloaded where appropriate.
- Payment notification sent/received.
- Wallet funded.
- Wallet debited/credited.
- Wallet transaction created/updated/reversed where supported.
- Withdrawal requested.
- Withdrawal approved.
- Withdrawal rejected.
- Bank account created/updated/deleted.
- Commission created/updated/paid.
- Payroll item created/updated/deleted.
- Salary generated/paid.
- Expense recorded/approved/rejected/deleted.
- Payment webhook received/processed/failed.
- External integration credential/settings changed, with secrets redacted.

Suggested event names:

- `finance.fee_created`
- `finance.fee_assigned`
- `payment.initiated`
- `payment.completed`
- `payment.failed`
- `payment.manual_proof_uploaded`
- `payment.manual_approved`
- `payment.manual_rejected`
- `wallet.funded`
- `wallet.credited`
- `wallet.debited`
- `withdrawal.requested`
- `withdrawal.approved`
- `withdrawal.rejected`
- `bank_account.created`
- `bank_account.updated`
- `commission.paid`
- `payroll.salary_paid`
- `expense.approved`
- `integration.webhook_received`
- `integration.webhook_failed`

Design requirements:

- Financial approval, rejection, reversal, bank-account changes, and credential changes should use severity `critical` or `security` where appropriate.
- Include amount, currency if present, transaction reference, payment provider, wallet/account identifiers, institution group, institution, payer/payee snapshots, and approval actor where relevant.
- Redact secrets, private keys, payment credentials, raw webhook secrets, authorization headers, and account-sensitive details.
- Do not store full raw webhook payloads if they may contain sensitive data. Store compact sanitized metadata and references.
- Avoid duplicate logs where a transaction model observer and a payment workflow event would produce the same meaning. Prefer the domain event.
- Ensure institution admins cannot see finance logs for other institutions/groups.

Frontend work:

- Extend audit filters for financial categories/severity if needed.
- Make financial logs easy to inspect in the detail view, especially amounts, references, statuses, and before/after changes.

Tests:

- Add focused Pest tests for:
  - Manual payment approval/rejection.
  - Wallet funding or debit/credit.
  - Withdrawal request/approval/rejection.
  - Bank account change with redaction.
  - Payment webhook processed/failed if webhook tests exist.
  - Cross-institution finance logs are not visible.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html`.
- Update `public/feature-docs/frontend.html` only if UI changed.
- Update `public/feature-docs/data.html` if payload conventions changed.

Verification:

- Run focused Pest tests for finance/payment areas and audit tests.
- Report changed files and test results.
```

## Stage 7: Messaging, Notifications, Media, Imports, Exports, Jobs, And System Activity

Use this prompt to cover operational activity that often happens outside normal CRUD screens.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement explicit audit logging for messaging, notifications, media, imports, exports, queued jobs, scheduled commands, and system/background activity.

Before making changes:

- Read `public/feature-docs/features.html`.
- Inspect feature sections for messaging/WhatsApp/BulkSMS/internal notifications, imports/exports/printable outputs/document handling, shared media tracking, live classes, timetable/school activities, to-do items, and background jobs/commands.
- Inspect existing controllers/actions/jobs/commands/tests for those areas.
- Inspect the existing audit foundation.
- If no audit foundation exists, create the minimal foundation needed to complete this stage.

Goal:
Record operational activity that may not be tied to simple model changes, including background and third-party interactions.

Required event areas:

- WhatsApp message sent/failed.
- BulkSMS message sent/failed.
- Internal notification sent/read/deleted where meaningful.
- Bulk message campaign started/completed/failed.
- Media uploaded.
- Media upload failed.
- Media deleted.
- Legacy media migration started/completed/failed.
- Import started/completed/failed.
- Export generated/downloaded.
- Printable output generated/downloaded where meaningful.
- Document uploaded/parsed/converted where present.
- AI document/question generation started/completed/failed where present.
- Scheduled command started/completed/failed where there are business-critical commands.
- Queued job completed/failed for business-critical jobs.
- Live class created/started/ended where present.
- Timetable or school activity created/updated/deleted.
- To-do item created/completed/deleted if appropriate.

Suggested event names:

- `messaging.whatsapp_sent`
- `messaging.whatsapp_failed`
- `messaging.bulk_sms_sent`
- `messaging.bulk_sms_failed`
- `notification.sent`
- `media.uploaded`
- `media.upload_failed`
- `media.deleted`
- `media.legacy_migration_completed`
- `import.started`
- `import.completed`
- `import.failed`
- `export.generated`
- `document.parsed`
- `ai.generation_started`
- `ai.generation_completed`
- `ai.generation_failed`
- `job.completed`
- `job.failed`
- `command.completed`
- `command.failed`

Design requirements:

- For bulk operations, log summary records with counts, file name, file type, target model, successful rows, failed rows, and sanitized error summaries.
- Do not store full imported files, exported files, message bodies with sensitive content, full AI prompts, full AI outputs, or full document text in audit logs.
- For third-party messaging/payment/provider failures, store provider name, reference IDs, response codes, sanitized messages, and status.
- Logs from queue/command context must work without HTTP request context.
- Preserve institution/institution group context when it can be derived.

Frontend work:

- Extend audit filters/categories if needed.
- Ensure operational logs with counts and statuses display clearly in the detail view.

Tests:

- Add focused Pest tests for:
  - Media upload/delete logs, if current tests make this practical.
  - Import completion/failure summary logs.
  - Export generated log.
  - Messaging send/failure logs if current messaging tests exist or can be added without external network calls.
  - Queue/command context log creation without request.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html`.
- Update `public/feature-docs/frontend.html` only if UI changed.
- Update `public/feature-docs/data.html` if payload conventions changed.

Verification:

- Run focused Pest tests for operational areas and audit tests.
- Report changed files and test results.
```

## Stage 8: Advanced Audit Log UI, Reporting, Export, Retention, And Integrity

Use this prompt after the foundation and several event groups exist. This stage improves usability, retention, and trustworthiness.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Implement advanced audit log UI, reporting, export, retention, and integrity features.

Before making changes:

- Read `public/feature-docs/features.html`.
- Inspect the existing audit log foundation, routes, controllers, pages, policies, tests, and docs.
- Inspect existing export/reporting patterns in the project.

Goal:
Make the audit log usable for real global managers, admins, and institution admins, while adding practical retention and tamper-evidence controls.

Required backend work:

- Add advanced filters:
  - Date range
  - Institution
  - Institution group
  - Actor
  - Actor role
  - Category
  - Event
  - Subject type
  - Subject search/name
  - Severity
  - IP address
  - Request ID
  - Impersonated-only
- Add export support for audit logs using existing project export conventions where possible.
- Add a scheduled pruning/retention command with configurable retention periods.
- Add retention categories such as normal operational logs, security logs, and financial logs.
- Add optional tamper-evidence using row hashes or a hash chain if practical without overcomplicating the system.
- Add tests for filtering, export authorization, pruning, and integrity behavior.

Required frontend work:

- Improve manager/global audit log page.
- Improve institution-scoped audit log page.
- Add a practical detail drawer/modal with:
  - Actor
  - Subject
  - Institution/institution group
  - Event/category/severity
  - Description
  - Request context
  - Impersonator context
  - Properties
  - Old/new value diff
- Add compact visual severity indicators.
- Add filters that match backend filters.
- Add export controls only where the current user is authorized.
- Keep UI consistent with existing Inertia + React/TypeScript + Chakra list/detail pages.
- Do not create a marketing-style page. This is an admin operations tool.

Design requirements:

- Audit logs must remain append-only for normal application users.
- No normal user should be able to edit logs.
- Deletion/pruning must be command/policy-controlled and tested.
- Export authorization must prevent institution admins from exporting cross-institution logs.
- Large exports should avoid memory-heavy implementations if the project already has streaming/queued export patterns.

Tests:

- Add Pest tests for:
  - Every advanced filter that is implemented.
  - Manager/global export includes authorized logs.
  - Institution export excludes other institutions.
  - Retention command prunes only eligible logs.
  - Security/financial logs retain longer than normal logs if configured.
  - Hash/integrity verification passes for unchanged logs and fails for tampered logs, if hash integrity is implemented.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html`.
- Update `public/feature-docs/frontend.html`.
- Update `public/feature-docs/data.html`.
- If product-facing behavior changes, update `public/feature-docs/product-recommendations.html` or `public/feature-docs/proposal.html` as appropriate.

Verification:

- Run focused Pest tests for audit logs.
- Run frontend lint/typecheck if frontend files changed.
- Report changed files and test results.
```

## Stage 9: Coverage Audit And Gap Closure

Use this prompt after several stages have been implemented. This stage asks Codex to review coverage, identify missing areas, and close practical gaps.

```markdown
You are working in `/Users/mac/Desktop/Projects/Web/edumanager`, a Laravel + Inertia React/TypeScript application.

Review audit log coverage across the whole project and close meaningful gaps.

Before making changes:

- Read `public/feature-docs/features.html`.
- Read the audit log sections in `public/feature-docs/backend.html`, `frontend.html`, and `data.html`.
- Inspect the existing audit log implementation, tests, routes, controllers, services, observers, jobs, commands, and UI.
- Review the implemented feature map in `features.html` and compare each feature area against current audit coverage.

Goal:
Produce a coverage-minded final pass so the audit system tracks every meaningful activity without creating excessive noise or sensitive data risk.

Required work:

- Create or update a concise audit coverage matrix in `public/feature-docs/features.html` or a linked audit documentation section.
- For each feature area, classify coverage as:
  - `covered`
  - `partially covered`
  - `not covered`
  - `intentionally excluded`
- Identify missing high-value events.
- Implement the most important missing events that are feasible in one Codex CLI run.
- Add tests for the gaps you close.
- Do not attempt risky huge refactors.
- Do not add noisy logs for insignificant reads or harmless page views unless they are security/finance/public-access sensitive.

Areas to explicitly check:

- Authentication/access/impersonation.
- Institution and partner onboarding.
- Manager administration.
- Institution dashboard/profile/settings/setup checklist.
- Users/staff/roles.
- Student lifecycle.
- Guardians/dependents.
- Classifications/groups/divisions/class mapping.
- Courses/course teachers/CCD/question bank.
- Curriculum/schemes/lesson plans/lesson notes.
- Assignments/submissions.
- Attendance/reports.
- Assessments/evaluations/comments.
- Result recording/processing/locking/publishing/reports.
- Result PINs.
- Exams/events/CBT/external exams/offline mock API.
- Admissions/admission forms.
- Fees/payments/receipts/notifications.
- Wallets/fundings/transactions/withdrawals/bank accounts/commissions.
- Payroll/salaries.
- Expenses.
- Messaging/WhatsApp/BulkSMS/internal notifications.
- Timetables/school activities/to-do/live classes.
- Associations/user associations.
- Payment/external integrations.
- Imports/exports/printable outputs/document handling.
- Shared query/filter/UI infrastructure where relevant.

Design requirements:

- Prefer high-signal domain events over raw noise.
- Avoid logging secrets or large content.
- Maintain institution scoping and manager/global visibility rules.
- Keep event naming stable and documented.

Tests:

- Add or update tests for every newly implemented gap.
- Add at least one authorization test if new visibility behavior is added.

Documentation:

- Update `public/feature-docs/features.html`.
- Update `public/feature-docs/backend.html`, `frontend.html`, and `data.html` as needed.
- Include the coverage matrix and any intentionally excluded events with reasons.

Verification:

- Run focused Pest tests for changed areas.
- Run broader audit test suite if practical.
- Run frontend lint/typecheck if frontend files changed.
- Report changed files, coverage improvements, and test results.
```

## Suggested Execution Order

The stages are designed to be independently understandable, but the smoothest execution order is:

1. Stage 1: Audit Log Foundation
2. Stage 2: Automatic Model Auditing
3. Stage 3: Authentication, Access, Roles, And Impersonation Audit Events
4. Stage 6: Finance, Payments, Wallets, Payroll, Expenses, And External Integrations Events
5. Stage 5: Results, Assessments, Exams, CBT, And Admissions Events
6. Stage 4: Student, Guardian, Class, Course, Curriculum, Assignment, And Attendance Events
7. Stage 7: Messaging, Notifications, Media, Imports, Exports, Jobs, And System Activity
8. Stage 8: Advanced Audit Log UI, Reporting, Export, Retention, And Integrity
9. Stage 9: Coverage Audit And Gap Closure

Finance and result-related stages are intentionally earlier than general academic operations because they carry higher audit and compliance risk.

## Notes For Running These Prompts

- Run one stage per Codex CLI session when possible.
- Commit after each stage once tests pass.
- If a stage becomes too large, ask Codex to stop after the backend and docs, then run a follow-up prompt for frontend/tests.
- Keep audit event names stable once implemented.
- Avoid logging raw secrets, full documents, full imported files, full AI prompts/outputs, full message bodies, or full webhook payloads.
- Prefer summary logs for bulk operations.
- Prefer explicit domain events for high-value workflows and automatic model auditing for simple CRUD.
