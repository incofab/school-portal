# Testing and operations

## Test harness

Pest is the primary test framework. `tests/Pest.php` binds Feature tests to `Tests\TestCase` and uses `DatabaseTransactions`. `Tests\TestCase::setUp()` disables Vite, clears the route resolver, and seeds `RoleSeeder` and `PermissionInventorySeeder` before each test. PHPUnit config defines Unit and Feature suites, uses the testing environment, array cache/session, synchronous queues, and disables Telescope.

Feature tests cover Actions, controllers, models, support classes, audit, AI, console commands, APIs, payment webhooks, and major module workflows. Unit tests exist for focused utilities/rules/registries. Tests are organized by the application namespace and are a strong guide to the behavior of each module.

## Test style

- Use Pest `beforeEach`, `it()`, `expect()`, Laravel helpers, factories, and database assertions.
- Prefer neighboring factories and states (`withInstitution`, `student`, `teacher`, `payable`, etc.) over hand-built records.
- For institution tests, create the institution, membership, related academic records, and act as the correct user.
- Assert Inertia component names and important props for page endpoints.
- Assert 403/redirect behavior for unauthorized roles and cross-institution access.
- Fake provider side effects with `Queue::fake()`, `Storage::fake()`, HTTP fakes, and test time where appropriate.
- Cover idempotency, status transitions, locks, ownership, validation, failure paths, and audit side effects for business workflows.
- Tests use real database transactions; do not rely on production `.env` data.

Representative executable references include `RecordStudentTest`, `RecordCourseResultTest`, `LessonPlanControllerTest`, `ProcessSettlementsTest`, the controller test trees under `tests/Feature/Http/Controllers`, and audit/AI test groups.

## Commands

Repository instructions document these normal commands:

```bash
composer install
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan serve
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build
./vendor/bin/sail npm run lint
./vendor/bin/pest
./vendor/bin/pint
npx prettier --write <files-worked-on>
```

Use the project’s local command conventions when Docker/Sail is available. Run the narrowest relevant test first, then the broader affected suite. Formatting applies to every PHP/TS file changed; documentation-only changes do not require code formatting.

## Queue and schedule

`config/queue.php` defaults to `sync` unless overridden. Database, Redis, SQS, and other Laravel queue drivers are configured. `app/Console/Kernel.php` schedules:

- a queue worker every minute, stopping when empty and without overlap;
- Telescope pruning daily;
- `audit:prune` daily at 02:30;
- `ai:prune-data` daily at 03:00 without overlap;
- `settlements:process` daily at 01:00 without overlap.

Commands also include role assignment, institution role seeding, withdrawal processing, pending-result publication, activity pruning, assistant pruning, legacy media migration, settlement processing, and tutorial database helpers. Inspect the command before assuming it is safe for production data.

## Runtime dependencies and infrastructure

Sail’s `docker-compose.yml` defines the Laravel app, MySQL, a separate MySQL test service, MinIO, and phpMyAdmin. Storage supports local/public and S3-compatible disks. Sentry, Telescope, Nightwatch, DOMPDF, Sanctum, Spatie Permission, PHPSpreadsheet, and Laravel AI are configured dependencies.

External configuration includes Paystack, Monnify, Payment Point, Rave, BulkSMS Nigeria, WhatsApp Cloud API, OpenAI/Laravel AI providers, AWS/S3, mail, PDF, and JWT settings. Use environment variables; never commit credentials.

## Tutorials and offline mocks

`tutorials/` contains Playwright/TypeScript walkthroughs for login, onboarding, fee payment, CBT exam, result recording, and feature overview. Tutorial Artisan commands seed isolated demo data and can snapshot/restore the database. The snapshot Action refuses production and checks allowed environments. `routes/api.php` also includes offline mock institution/event/exam endpoints. These are test/tutorial support surfaces, not a general replacement for live module behavior.

