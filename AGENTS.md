# Repository Guidelines

## Project Structure & Module Organization

- Laravel core lives in `app/` (Actions, Jobs, Models, Rules, Helpers) with HTTP entry points in `routes/web.php` and `routes/api.php` plus feature-specific route files under `routes/`.
- Frontend is Inertia + React/TypeScript under `resources/js` with Chakra UI, shared components/hooks, and Vite entry `resources/js/app.tsx`; styles start in `resources/css/app.css`.
- Database assets are in `database/migrations`, `database/seeders`, and `database/factories`; seeders include roles and sample domain data.
- Public entry is `public/index.php`; built assets are published to `public/build` by Vite. Tests live in `tests/Feature` using Pest.
- `public/feature-docs/`: The project knowledge base for AI CLIs and maintainers. Start with `public/feature-docs/features.html` for the implemented feature map, then use `backend.html`, `frontend.html`, and `data.html` for route/controller/action, UI, model, and migration details.

## Feature Documentation & AI Orientation

- Before implementing or reviewing any change, read `public/feature-docs/features.html` to identify the affected feature area and its linked controllers, actions, models, frontend pages, and tests.
- Use the docs as the current source of truth for the project feature map. The implemented feature areas currently documented are: authentication/access/impersonation; institution and partner onboarding; manager administration; institution groups; academic sessions; institution dashboard/profile/settings/setup checklist; users/staff/roles; student lifecycle; guardians/dependents; classifications/groups/divisions/class mapping; courses/course teachers/CCD/question bank; curriculum/schemes/lesson plans/lesson notes; assignments/submissions; attendance/reports; assessments/learning evaluations/comments; result recording/processing/locking/publishing/reports; result PINs; exams/events/CBT/external exams/offline mock API; admissions/admission forms; fees/payments/receipts/payment notifications; wallets/fundings/transactions/withdrawals/bank accounts/commissions; payroll/salaries; expenses; messaging/WhatsApp/BulkSMS/internal notifications; timetables/school activities/to-do/live classes; associations/user associations; payment/external integrations; imports/exports/printable outputs/document handling; and shared query/filter/UI infrastructure.
- When a feature is added, removed, renamed, or behaviorally changed, update `public/feature-docs/features.html` in the same change. Also update `backend.html` for route/controller/action changes, `frontend.html` for Inertia/React/page/component changes, `data.html` for model/migration/schema changes, and the proposal/recommendation pages if product-facing descriptions change.
- Do not leave feature documentation stale. Every code update that changes project behavior should include a docs update, even when the implementation is small.
- When handing off to another AI CLI, point it to `public/feature-docs/features.html` first, not only to source folders, so it understands the full product surface before editing.

## Build, Test, and Development Commands

- Install deps: `composer install` and `npm install` (or `./vendor/bin/sail npm install` when using Sail).
- Run app: `sail artisan serve` for PHP + `npm run dev` for Vite; Sail alternative: `./vendor/bin/sail up` then `./vendor/bin/sail npm run dev`.
- Production build: `npm run build`.
- Lint frontend/TS: `npm run lint` (eslint + tsc).
- Backend tests: `sail artisan test` or `./vendor/bin/pest`; use `.env.testing` with a dedicated database.
- Database setup: `sail artisan migrate --seed` (or `./vendor/bin/sail artisan migrate --seed`) to load baseline data such as roles.

## Coding Style & Naming Conventions

- PHP: follow PSR-12; format with `./vendor/bin/pint`. Use StudlyCaps for classes, camelCase for methods/properties, and typed properties/returns where possible.
- React/TypeScript: components in PascalCase, hooks prefixed with `use*`, props/interfaces in `UpperCamelCase`. ESLint forbids `console` and enforces strict equality; Prettier uses single quotes and 2-space tabs.
- Prefer DTO/Action patterns already in `app/Actions` and reuse shared helpers (`app/helpers.php`) before adding new globals.

## Testing Guidelines

- Write Pest feature tests alongside the code in `tests/Feature`; leverage factories and seeders to keep fixtures consistent.
- Use database transactions (default in `tests/Pest.php`) and seed required roles/permissions; avoid depending on production `.env`.
- Cover new endpoints, commands, and business rules; for UI-facing changes, add minimal server assertions (HTTP status, inertia props, authorization).

## Commit & Pull Request Guidelines

- Keep commit messages short and action-led, matching current history (`whatsapp messaging capability`, `fixes`); prefer present-tense imperatives like `add`, `fix`, `refactor`.
- For PRs, include: summary of change, linked issue/ticket, test command(s) run, and screenshots/GIFs for UI tweaks.
- Call out migrations or env changes explicitly so reviewers can apply them.

## Environment & Security Notes

- Copy `.env.example` to `.env` and set `APP_KEY` before running; `.env.testing` is available for automated tests.
- Sail (`docker-compose.yml`) provides MySQL and MinIO; keep ports aligned with `APP_PORT`/`VITE_PORT` if customized.
- Never commit secrets, API keys, or generated files from `storage/` or `public/build`; prefer `.gitignore` patterns already present.

## Important Note

- Before you perform any task, Study the style, theme, structure and convention followed in this project.
- Your implementation should follow this structure and coding style,
- Try to reuse what has already been defined, but when you need to create one, Make your code resuable and
- If you have any questions or need clarifications, ask the questions at once before you start. Responses will be provided to all your questions.
- Where possible, Make reasonable assumptions where necessary
- Add wide covering tests for every feature you implement, following the style and structure of the existing tests.
- When features are added or updated, the documentation should be updated to reflect the changes. The documentation should at all times contain the latest detailed information about the project.
