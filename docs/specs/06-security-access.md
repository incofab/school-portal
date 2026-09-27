# Security, tenancy, and access control

## Authentication surfaces

`config/auth.php` defines a session `web` guard backed by `User`, an `admin` session guard backed by `Admin`, and an `api` token guard. Most current web routes use the `User`/`web` guard. Sanctum is installed for API/stateful frontend authentication and `routes/api.php` uses `auth:sanctum` for protected API groups.

Guest/auth routes in `routes/web.php` cover login, student login, password reset, registration, public result activation/lookup, and exam entry. Authenticated users can select institutions, manage their profile/password, view payment attempts, and impersonation can be started/stopped through manager routes.

## Institution boundary

Institution-scoped routes use `{institution}` plus `institution.user`. The boundary is enforced in several layers:

1. `Institution::resolveRouteBinding` joins `institution_users` and restricts non-manager users to institutions they belong to.
2. `VerifyInstitutionUser` rejects missing bindings, inactive institutions, managers accessing tenant pages directly, non-members, suspended memberships, and inactive memberships; it shares the institution and merges `institution_id` into the request.
3. Models using `InstitutionScope` automatically filter `institution_id` using `currentInstitution()`.
4. `currentInstitution()`, `currentInstitutionUser()`, and `currentUser()` are the canonical helpers.
5. Controllers, policies, and ownership checks add feature-specific restrictions.

Never rely on a URL institution parameter alone. Query and validate through the current institution context, and be careful with `withoutGlobalScopes()` and raw queries.

## Roles and permissions

Platform roles are `manager-admin` and `partner` (`ManagerRole`). Institution user types are `admin`, `teacher`, `accountant`, `student`, `alumni`, `guardian`, and `others` (`InstitutionUserType`). Institution memberships also have `active`/`suspended` status.

Spatie Permission is configured with the custom `AppModelsRole` and web guard. Institution roles are institution-owned; global manager roles have a null `institution_id`. `InstitutionRoleService` creates default institution roles and assigns default permissions. `InstitutionPermission` currently contains:

`manage-roles`, `manage-admissions`, `manage-recruitment`, `manage-library`, `manage-attendance`, `manage-fees`, `manage-finance`, `manage-payroll`, `manage-notifications`, and `manage-chat`.

Default permissions are defined in the enum: admins receive `*`; teachers receive library/attendance/chat; accountants receive fees/finance/payroll/notifications/chat; student/alumni/guardian receive chat; others receive none. Verify the enum and role service before relying on this summary.

Backend enforcement uses controller middleware helpers, route middleware, policies, feature-specific ownership checks, and explicit model logic. Frontend enforcement uses `PermissionGate` and role hooks only to hide or show controls; it is not a security boundary.

## Policies and ownership

Policies exist for activity logs, assignments, assistant diagnostics, courses, FAQs, institution groups/institutions, payment references, students, and users. Many institution controllers also enforce teacher ownership or admin access directly, for example course-teacher ownership for lesson plans and class/form-teacher ownership for results.

`InstitutionUser::canViewResultsFor()` is relationship-based: it permits same-institution admins, the student, a related guardian, the student's form teacher, or a teacher assigned to the student's class/course. Result access should use this rule or its existing controller/service path instead of creating a broad “view results” bypass.

`StudentAccess` permits the student, an institution user, or a guardian linked through `GuardianStudent`; unauthorized access is logged and returns 403 for JSON or redirects for browser requests.

## Impersonation

Managers must impersonate institution users before reaching institution pages. Impersonation state is stored in session keys such as `impersonator_id` and `impersonator_type`, shared to Inertia, displayed by `ImpersonationBanner`, and recorded in security audit data. Guardian-to-student flows also exist. Preserve the visible banner and explicit stop-impersonating path when adding impersonated workflows.

## AI access control

`routes/assistant.php` provides guest assistant routes under `web`, manager diagnostics under `web`, `auth`, `manager`, and institution assistant routes under `web`, `auth`, `institution.user`. Assistant action tools use preview/confirmation records, idempotency keys, confirmation token hashes, and institution/user context. Read tools are registered separately from action tools in `AppServiceProvider`.

AI has a dedicated rate limiter in `RouteServiceProvider` and configuration for input/context limits, calls, budgets, retention, telemetry, and guest access. Do not expose a new write-capable tool without following the existing preview/confirm/cancel and authorization patterns.

## API and external access

API routes include Sanctum login/user endpoints, institution attendance, payment webhooks, WhatsApp verification/receiving, offline exam/event mocks, and a CCD question API. External exam/token-user routes are public/token-based and use `TokenUser`, JWT helpers, and the external controllers. Check the exact route middleware before assuming a web session exists.

## Audit and sensitive data

Security events are logged through `SecurityActivityLogger`; academic score changes use academic-integrity logging; financial changes use financial logging. Audit payload sanitizers intentionally omit or redact secrets, raw PINs, uploaded files, full exam attempts, document contents, and other large/sensitive values. Follow `ActivityLogSanitizer`, `AssistantRedactor`, and existing logging wrappers.

