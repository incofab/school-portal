# Repository Guidelines

## Project Structure & Module Organization
- Laravel core lives in `app/` (Actions, Jobs, Models, Rules, Helpers) with HTTP entry points in `routes/web.php` and `routes/api.php` plus feature-specific route files under `routes/`.
- Frontend is Inertia + React/TypeScript under `resources/js` with Chakra UI, shared components/hooks, and Vite entry `resources/js/app.tsx`; styles start in `resources/css/app.css`.
- Database assets are in `database/migrations`, `database/seeders`, and `database/factories`; seeders include roles and sample domain data.
- Public entry is `public/index.php`; built assets are published to `public/build` by Vite. Tests live in `tests/Feature` using Pest.

## Build, Test, and Development Commands
- Install deps: `composer install` and `npm install` (or `./vendor/bin/sail npm install` when using Sail).
- Run app: `php artisan serve` for PHP + `npm run dev` for Vite; Sail alternative: `./vendor/bin/sail up` then `./vendor/bin/sail npm run dev`.
- Production build: `npm run build`.
- Lint frontend/TS: `npm run lint` (eslint + tsc).
- Backend tests: `php artisan test` or `./vendor/bin/pest`; use `.env.testing` with a dedicated database.
- Database setup: `php artisan migrate --seed` (or `./vendor/bin/sail artisan migrate --seed`) to load baseline data such as roles.

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
