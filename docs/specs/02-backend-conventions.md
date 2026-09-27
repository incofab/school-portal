# Backend conventions

## Naming and organization

- Classes use StudlyCaps; methods and properties use camelCase.
- Controllers are grouped by surface and domain under `app/Http/Controllers/Institutions`, `Managers`, `API`, `Auth`, `AI`, `CCD`, `Impersonate`, and `Home`.
- Actions live in `app/Actions/<Domain>` and are named for a verb/workflow, such as `RecordCourseResult`, `ProcessSessionResult`, `RecordMessage`, or `InitializePublicStudentFeePayment`.
- Services are concentrated in `app/Services/AI`, `Messaging`, `Curriculum`, `Institutions`, and `Results`.
- Enums under `app/Enums` are used for persisted statuses, roles, settings, payment values, and result modes. Prefer an existing enum over new string literals.
- DTOs under `app/DTO` carry structured data for payments, payouts, sheets, results, and AI.
- Shared helpers are autoloaded from `app/helpers.php`; use an existing helper such as `currentUser()`, `currentInstitution()`, `currentInstitutionUser()`, `paginateFromRequest()`, `instRoute()`, `successRes()`, or `failRes()` when it matches the need.

## Controllers

Controllers extend `AppHttpControllersController`, which supplies:

- `allowedRoles()` and `allowedPermissions()` controller middleware helpers.
- `onlyAdmins()` and ownership helpers for common institution checks.
- `ok()` and `message()` for JSON mutation responses.
- `apiRes()` and `successApiRes()` for API responses.
- `res()` and a legacy `redirect()` helper for `Res`-based redirect flows.

The dominant page pattern is a thin controller that builds a query, applies a `UITableFilters` class, eager-loads relationships, paginates, and returns an Inertia page. Some older controllers contain more direct model mutation; follow the nearest current implementation rather than forcing a wholesale rewrite.

Resource routes are used extensively, but custom route names are common for workflows such as `store-or-update`, `calculate`, `send-results`, `upload`, `transfer`, `generate-payroll`, and `update-status`. Preserve existing route names and HTTP verbs when extending a module.

## Validation

Form Requests are in `app/Http/Requests` with curriculum and exam subdirectories. Examples include `CreateStudentRequest`, `RecordCourseResultRequest`, `LessonPlanRequest`, `CurriculumMediaRequest`, `StoreExamRequest`, `CreateFeeRequest`, and report-specific requests.

Validation is also present in model static rule methods (`User::generalRule`, `Institution::generalRule`, `Course::createRule`, etc.), custom Rules under `app/Rules`, and controller-level checks. `InstitutionRoleService` centralizes role selection validation so staff forms and assistant staff actions accept the same role set.

When adding a request:

1. Inspect the neighboring endpoint to see whether it uses a Form Request, model rule, or inline validation.
2. Keep institution ownership and cross-record existence checks institution-scoped.
3. Use enum validation for enum-backed input and the project custom rules where they already solve the problem.
4. Test both validation failures and authorization failures.

## Actions, services, and `Res`

Actions are the preferred home for multi-step business behavior. They commonly expose `make()`, `run()`, `create()`, `update()`, or `execute()` entry points and receive typed models/data through constructors.

Use a service when behavior is an integration, registry, or cohesive subsystem rather than a one-off workflow. Existing examples include `MessageDispatcher`, WhatsApp services, `CurriculumMediaService`, `InstitutionRoleService`, payment processors/merchants, and AI registries.

`AppSupportRes` is an `ArrayObject` carrying `success`, `message`, and optional payload. `successRes()` and `failRes()` construct it. Actions such as attendance notification and payment handlers use it when callers need a uniform success/failure result. Preserve that contract when adding a similar action.

Do not extract every two-line query into a new class. Extract when the workflow is reused, has meaningful branching/side effects, or needs focused tests.

## Queries, filters, pagination, and eager loading

- Institution list endpoints normally use `paginateFromRequest($query)`, which reads `perPage` (default 100) and `page` from the request.
- `app/Support/Queries` contains domain query builders for institutions, students, assessments, assignments, and libraries.
- `app/Support/UITableFilters` contains server-side filter objects. Frontend filter drawers write query parameters and the controller applies the matching filter object.
- `ServerPaginatedTable` reads URL `page`, `sortKey`, and `sortDir`; keep backend sort keys aligned with the page header's `sortKey`.
- Eager-load relationships required by the page and avoid changing list data shape without updating the page prop type and test assertions.
- Model-level `InstitutionScope` must be respected in institution requests. Use `withoutGlobalScopes()` only where the code has an explicit cross-tenant/platform reason.

## Persistence and transactions

`DB::transaction()` is used in selected multi-record workflows. Some older Actions use explicit `beginTransaction()`/`commit()` (for example `RecordStudent`); preserve rollback safety when touching such code. Financial, result, role, and user-association mutations deserve transaction and idempotency review.

Models often use guarded empty arrays, casts, relationship helpers, and soft deletes. Do not assume every model is soft-deletable; verify the model and migration.

## Files, exports, and imports

File work uses the media subsystem (`HasMedia`, `MediaManager`, `Media`, `S3Folder`, and `CurriculumMediaService`) for many institution attachments and images. Storage disks are configured in `config/filesystems.php` (`local`, `public`, `s3`, and `s3_public`).

Excel imports/exports use PHPSpreadsheet and project Actions such as `ConvertSheetToArray`, result/staff/student recording-sheet handlers, `GenericExport`, and module-specific download Actions. Validate uploaded spreadsheets with the existing Excel rules/enums and test with fake storage/uploaded files.

PDF/print behavior exists in DOMPDF-backed controllers, Blade exam/result views, React result templates, and client-side PDF/export utilities. Follow the module's existing output path; do not replace print surfaces with a new renderer without checking its consumers.

## Background jobs and integrations

`SendBulksms` and `SendWhatsappTemplateMessage` implement `ShouldQueue`. They update `Message` status, handle retry/failure behavior, and in the SMS case charge institution messages through `ChargesInstitutionMessage`. The scheduler runs a queue worker every minute in addition to provider/pruning/settlement commands.

Provider integrations are wrapped in `app/Core`, `app/Support/Payments`, `app/Services/Messaging`, or dedicated webhook controllers. Existing payment providers include Paystack, Monnify, Payment Point, Rave, wallet/manual flows; messaging includes SMS, email, and WhatsApp. Reuse the merchant/processor contracts and DTOs rather than calling a provider directly from a page controller.

## Errors, logging, and audit

Authorization failures generally abort with HTTP 403 for JSON requests and redirect to a project error/dashboard route for browser requests. Security-sensitive failures are sent to `SecurityActivityLogger`. Domain events use the audit wrappers under `app/Support/Audit`; automatic model auditing is registered through `ModelAuditRegistry` and can be suppressed with `ModelAudit::withoutAuditingFor` when a richer explicit event is emitted.

Avoid logging secrets, raw payment credentials, PIN values, uploaded document content, or full exam/result payloads. Use the existing sanitizers and audit payload conventions.

