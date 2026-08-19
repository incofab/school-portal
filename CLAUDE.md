# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

EduManager is a multi-tenant SaaS platform for educational institutions (students, admissions, results/exams, attendance, fees/payments, payroll, messaging, etc.). Laravel 12 (PHP 8.2) backend + Inertia.js + React/TypeScript frontend, styled with Chakra UI, bundled with Vite.

There is a companion Flutter app in `edumanager_flutter/` (separate codebase/tooling, not covered here unless asked).

A living feature knowledge base is maintained at `public/feature-docs/` — start with `features.html` for the implemented feature map, then `backend.html`, `frontend.html`, `data.html` for route/controller/action, UI, and model/migration details. **Keep these docs updated when features are added or changed.**

## Commands

Install:
```bash
composer install
npm install
```
(or `./vendor/bin/sail composer install` / `./vendor/bin/sail npm install` when using Sail — Sail provides MySQL + MinIO via `docker-compose.yml`)

Run app:
```bash
php artisan serve      # or ./vendor/bin/sail up
npm run dev            # Vite HMR
```

Build frontend for production:
```bash
npm run build
```

Frontend lint/typecheck:
```bash
npm run lint            # eslint resources/js && tsc
```

Backend format (Pint, PSR-12):
```bash
./vendor/bin/pint
```

Format every file you worked on (PHP included — `@prettier/plugin-php` is installed, so this is required in addition to Pint, not instead of it):
```bash
npx prettier --write <files>
```

Backend tests (Pest):
```bash
./vendor/bin/pest                                  # all tests
./vendor/bin/pest tests/Feature/Path/To/Test.php    # single file
./vendor/bin/pest --filter="test description"       # by name
```
Tests use `.env.testing`, run inside DB transactions (`tests/Pest.php`), and auto-seed `RoleSeeder` before each Feature test. Don't depend on production `.env` data.

Environment setup: copy `.env.example` to `.env` and run `php artisan key:generate` before first run; `.env.testing` is for automated tests only.

DB setup (seeders include roles/permissions and sample domain data — needed for a usable local install, not just tests):
```bash
php artisan migrate --seed
```

## Architecture

### Multi-tenancy hierarchy

`Manager` (platform/partner admin) → `Institution` (a school, tenant) → `InstitutionUser` (staff/admin/teacher membership within an institution) → `Student` / `Guardian` records tied to that institution.

- **Route-level tenancy**: institution-scoped routes are prefixed with `{institution}` and load through `App\Providers\RouteServiceProvider::boot()`, which wires three separate route groups with distinct middleware/prefix/name combos:
  - `routes/manager.php` → `manager` prefix, `manager` middleware, `managers.` route names — platform/partner admin area.
  - `routes/institution.php` and `routes/ccd.php` → `{institution}` prefix, `institution.user` middleware, `institutions.` route names — tenant-scoped app.
  - `routes/web.php` / `routes/api.php` — public/shared/auth routes.
- **`institution.user` middleware** (`App\Http\Middleware\VerifyInstitutionUser`) resolves the bound `Institution` from the route, verifies the current user is that institution's active/non-suspended `InstitutionUser`, blocks managers from accessing tenant pages directly (they must impersonate), and shares `institution` to views.
- **Model-level tenancy**: models using the `App\Traits\InstitutionScope` trait get an automatic global scope filtering all queries by `institution_id` of `currentInstitution()`. Do not bypass this with raw queries inside institution-scoped code paths.
- Global helpers in `app/helpers.php`: `currentUser()`, `currentInstitution()` (from the bound route param), `currentInstitutionUser()`. Reach for these instead of re-deriving tenant context.
- **Impersonation**: managers impersonate institution admins/users via `App\Http\Controllers\Impersonate\*` — this is how platform staff can access tenant-scoped pages during support.

### Backend structure (`app/`)

Business logic favors an **Action pattern** over fat controllers/services: `app/Actions/<Domain>/<Verb...>.php` (e.g. `app/Actions/Attendance/SendDailyAttendanceNotifications.php`, `app/Actions/Fees`, `app/Actions/Result`, `app/Actions/Payments`). Controllers are thin and delegate into Actions. Prefer reusing/extending an existing Action before adding a new global helper or duplicating logic.

Controllers under `app/Http/Controllers/` mirror the tenancy split: `Institutions/*` (heavily subdivided by domain — `Attendance`, `Results`, `Admissions`, `Exams`, `Payments`, `Withdrawals`, `Chats`, `Staff`, `Recruitment`, etc.), `Managers/*` (platform admin), `API/*`, `Impersonate/*`, `Auth/*`, `Home/*`.

Other notable `app/` directories: `DTO`, `Enums`, `Services` (e.g. `Services/Messaging/*` for SMS/WhatsApp dispatch — see `Services/Messaging/MessageDispatcher`, `Services/Messaging/Whatsapp/PhoneNumberNormalizer`), `Repositories`, `Policies`, `Observers`, `Support` (e.g. `Support/Audit/SecurityActivityLogger`), `Traits`, `Rules`, `Casts`, `Notifications`, `Jobs` (queued work, e.g. `SendBulksms`, `SendWhatsappTemplateMessage`).

Scheduled/console commands live in `app/Console/Commands`, registered in `app/Console/Kernel.php`'s `schedule()`.

### Frontend structure (`resources/js/`)

- Inertia entry: `resources/js/app.tsx`; styles start at `resources/css/app.css`.
- Pages: `resources/js/Pages/**` — mirrors backend domains, split into `institutions/*` (tenant app pages), `managers/*` (platform admin pages), `users/*`, `auth/*`, `payments/*`, `notifications/*`.
- Shared UI: `resources/js/components/**` (data tables, forms, modals, selectors, page headers, etc. — reuse these before building new one-off UI), plus `resources/js/domain`, `resources/js/hooks`, `resources/js/layout`, `resources/js/types`, `resources/js/util`.
- Chakra UI is the styling system project-wide; don't introduce a second one.
- Database assets: `database/migrations`, `database/seeders`, `database/factories`.
- Public entry is `public/index.php`; Vite publishes built assets to `public/build` (generated — don't hand-edit or commit ad hoc changes there).

### Testing conventions

Pest feature tests live in `tests/Feature`, generally colocated by domain (e.g. `tests/Feature/Console/...`). Use factories/seeders for fixtures (e.g. `Institution::factory()`, `Student::factory()->withInstitution(...)`, `Attendance::factory()->institutionUser(...)`). Fake external side effects (`Queue::fake()`, `Carbon::setTestNow()`) rather than hitting real SMS/WhatsApp/payment providers.

Write wide-covering Pest tests for every feature you implement (new endpoints, commands, and business rules), following the style/structure of neighboring tests. For UI-facing changes, add at least minimal server-side assertions (HTTP status, Inertia props, authorization).

## Conventions

- PHP: StudlyCaps classes, camelCase methods/properties, typed properties/returns where practical, PSR-12 via Pint.
- React/TS: PascalCase components, `use*`-prefixed hooks, `UpperCamelCase` props/interfaces. ESLint forbids `console` and enforces strict equality; Prettier uses single quotes + 2-space indent.
- Reuse `app/Actions` and `app/helpers.php` before adding new globals or duplicating logic.
- Commit messages: short, action-led, present-tense imperative (matches existing history — e.g. `fixes`, `minor fixes`).
- PRs should include: summary of the change, linked issue/ticket, the test command(s) actually run, and screenshots/GIFs for UI tweaks.
- Call out migrations or env changes explicitly when describing changes, since reviewers need to apply them manually.
- An `.agents/skills/edumanager-ui-ux/SKILL.md` skill exists with detailed UI/UX standards (Chakra usage, responsive/accessibility rules, states, forms, tables) — consult it for any user-facing frontend work; it mandates preserving existing routes/APIs/permissions/business logic while improving presentation.

## Working conventions (explicit project policy)

- Study this project's existing style, structure, and conventions for the area you're touching before writing code; match it rather than introducing a new pattern.
- Reuse what's already defined (Actions, helpers, shared components) before creating something new; when you do add something new, make it reusable and abstract out logic that's shared across call sites (DRY).
- If a task has open questions, ask all of them up front in one batch before starting implementation, rather than trickling questions in one at a time or guessing silently on decisions that matter.
