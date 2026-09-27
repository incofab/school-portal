# System architecture

## Application shape

EduManager is a single Laravel application with a React/TypeScript interface mounted through Inertia. The backend owns routing, authentication, authorization, validation, domain logic, persistence, exports, webhooks, and most workflow decisions. React pages render the Inertia component named by the controller and call named Laravel routes for navigation and mutations.

Observed runtime foundations:

- PHP `^8.3` and Laravel `^13.0` are declared in `composer.json`.
- React 18, TypeScript, Inertia React, Chakra UI, React Pro Sidebar, Vite, Ziggy, Axios, React Select, Chart.js, TinyMCE, XLSX, jsPDF, and React Dropzone are declared in `package.json`.
- `public/index.php` is the web entry point.
- `resources/views/app.blade.php` is the Inertia root view.
- `resources/js/app.tsx` is the React bootstrap and uses Vite.
- `webpack.mix.js` is still present, but the active Vite entry is configured in `vite.config.js`; do not assume the Mix pipeline is the current frontend build path.

## Route loading and URL namespaces

`app/Providers/RouteServiceProvider.php` loads route groups as follows:

| Source | Prefix | Name prefix | Middleware | Purpose |
| --- | --- | --- | --- | --- |
| `routes/api.php` | `/api` | `api.` | `api` | Sanctum-protected API, webhooks, offline mock, and API helpers |
| `routes/web.php` | none | none | `web` | Public, authentication, user, external exam, payment, and shared routes |
| `routes/assistant.php` | `/assistant`, `/manager/ai-assistant`, or `/{institution}/assistant` | `assistant.`, `managers.ai-assistant.`, `institutions.assistant.` | declared in that file | Guest/authenticated assistant surfaces and manager diagnostics |
| `routes/manager.php` | `/manager` | `managers.` | `web`, `auth`, `manager` | Platform manager and partner area |
| `routes/ccd.php` | `/{institution}/ccd` | `institutions.` | `web`, `auth`, `institution.user` | Course-content/question-bank area |
| `routes/institution.php` | `/{institution}` | `institutions.` | `web`, `auth`, `institution.user` | Institution dashboard and operational modules |

`routes/institution.php` includes `assignment.php`, `attendance.php`, `libraries.php`, `exam.php`, and `student_routes.php`, so those files are institution-scoped when reached through the main provider. `routes/web.php` also declares public versions of selected admission, recruitment, manual payment, exam, and external routes.

The `morphable` route parameter is custom-bound through `MorphableHandler` in `RouteServiceProvider`. Institution route binding is implemented by `Institution::resolveRouteBinding`, which uses the institution UUID by default and joins `institution_users` for non-manager users.

## Request lifecycle

The web middleware group is defined in `app/Http/Kernel.php` and includes encrypted cookies, sessions, shared session errors, the project CSRF middleware, route bindings, and `HandleInertiaRequests`. The API group includes API throttling, route bindings, and Sanctum's stateful middleware.

Typical page visit:

1. A named route resolves a controller and any bound models.
2. Authentication and, for institution routes, `VerifyInstitutionUser` run.
3. The controller applies role/permission/ownership checks, request validation, filters, and eager loading.
4. A page action returns `Inertia::render('path/to/page', props)`.
5. Inertia resolves that string to `resources/js/pages/path/to/page.tsx`.
6. The page uses shared layouts and components and navigates with Ziggy/Inertia.

Typical mutation:

1. A page submits with `useWebForm`/Axios or an Inertia visit.
2. A Form Request or controller/model rule validates input.
3. The controller delegates meaningful work to an Action, service, or support handler where one exists.
4. The endpoint commonly returns JSON shaped as `{ ok: true, ... }`, `{ message: ... }`, or a `Res`-derived response.
5. The page displays a Chakra toast and either visits a list page or reloads selected Inertia props.

Other response modes are deliberate: redirects, downloads, PDFs, Blade views for legacy/exam/print surfaces, webhook responses, and API JSON responses all exist.

## Backend layer boundaries

| Layer | Current responsibility |
| --- | --- |
| Routes | URL, HTTP verb, route name, middleware, resource grouping, and route model parameters |
| Controllers | HTTP orchestration, page props, request/response translation, and local access checks |
| Form Requests | Reusable authorization/validation rules for the requests that use them |
| Actions | Reusable business workflows, especially results, payments, fees, students, messages, attendance, exams, and imports/exports |
| Services | Stateful/integration-oriented behavior such as AI, messaging, curriculum media, and institution roles |
| Support | Response wrapper, query builders, filters, media, payments/payouts, settings, reporting, audit, and registries |
| Models | Persistence, relationships, casts, scopes, domain helpers, and some validation rules |
| Jobs | Asynchronous provider calls for SMS and WhatsApp |
| Policies/middleware | Cross-request and object-level authorization |

There is one `app/Repositories` area, but repositories are not the dominant application pattern. Existing code favors Eloquent queries plus Actions, Services, Support query builders, and `UITableFilters`.

The repository currently has a small `CourseSessionRepository`, three mailables under `app/Mail`, and a `ModelAuditObserver`. There are no application classes under `app/Events`, `app/Listeners`, or `app/Notifications`; notification behavior is currently implemented through internal-notification models/controllers, mailables, messaging services/jobs, and provider integrations.

## Frontend layer boundaries

- `resources/js/pages` contains route-level React pages. The actual directory is lowercase `pages`, and page names match the Inertia component strings.
- `resources/js/layout` contains institution and manager shells.
- `resources/js/components` contains shared tables, form controls, selectors, modals, buttons, slabs, filters, and domain-agnostic UI.
- `resources/js/domain` contains reusable domain-specific UI such as user profiles and print layouts.
- `resources/js/hooks` reads shared props, role/permission context, query strings, form state, modal state, and toast state.
- `resources/js/util` contains route wrappers, formatting, export, exam, result, upload, and browser utilities.
- `resources/js/types` contains shared frontend model and permission types; many page props remain locally typed interfaces.

There is no single global React store visible in the current frontend. Page state, Inertia props, local component state, custom hooks, and URL query parameters are the normal state mechanisms.

## Service-provider registrations

`AppServiceProvider` binds the AI text generator and knowledge retriever contracts, builds singleton assistant read/action registries, enforces the project morph map, registers the model audit observer for models returned by `ModelAuditRegistry`, and changes the session cookie name per host for multi-domain support. Add new cross-cutting bindings here only when an existing binding/registry pattern is a real fit.
