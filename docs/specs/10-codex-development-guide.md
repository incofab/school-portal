# Future Codex development guide

## Before coding

1. Identify the module from [07-module-map.md](07-module-map.md).
2. Read the relevant route file, controller, Action/service, models/migrations, React page, shared components, and nearest feature tests.
3. Check whether the work is institution-scoped, manager-scoped, public, API, external-token, or tutorial-only.
4. Search for an existing selector, table, filter, modal, hook, Action, query builder, media handler, payment processor, or audit wrapper before creating one.
5. Check existing enums, settings, permissions, route names, and shared Inertia props before adding string/config duplicates.

## Implementation rules

- Preserve routes, named route contracts, middleware, permissions, data shapes, and established workflows unless the request explicitly changes them.
- Keep controllers focused on HTTP orchestration. Put reusable/multi-step logic in an existing or well-justified Action/service/support class.
- Respect `currentInstitution()` and `InstitutionScope` for all institution-owned reads/writes.
- Enforce authorization on the server even when the frontend hides a control with `PermissionGate`.
- Use the existing `useWebForm`/Axios response-and-toast pattern for pages in that family; use Inertia form/navigation conventions when the neighboring page does.
- Use `DashboardLayout`/`ManagerDashboardLayout`, `Slab`, shared buttons, selectors, tables, filters, modals, and `useInstitutionRoute()` rather than one-off equivalents.
- Keep responsive Chakra props, light/dark values, accessible labels, and print behavior for relevant surfaces.
- Use enums and DTOs already present for statuses and structured integration data.
- Preserve audit logging and avoid sensitive values in logs/audit payloads.
- Use transactions/idempotency for workflows where existing Actions and tests establish that expectation.
- Avoid a new dependency or architectural layer unless existing code cannot reasonably support the change.

## Change checklist

### Backend

- [ ] Route is in the correct file/group and uses the established name/HTTP verb.
- [ ] Binding and tenant context are safe.
- [ ] Form Request/model/custom Rule validation matches neighboring code.
- [ ] Role, permission, policy, and ownership paths are covered.
- [ ] Action/service/support extraction is justified and reusable.
- [ ] Eager loads, filters, pagination, response shape, downloads, or redirects are compatible with existing pages.
- [ ] Transactions, status transitions, queues, provider calls, and audit entries are handled.

### Frontend

- [ ] Inertia component name exactly matches the page path.
- [ ] Shared props/types and serialized relationship names are used correctly.
- [ ] Existing layout and reusable components are reused.
- [ ] Form errors, processing state, toasts, empty/loading/error state, and reload/redirect behavior are present.
- [ ] Permission/role visibility is consistent with the backend, without treating UI gating as authorization.
- [ ] Desktop, tablet, mobile, dark mode, and print behavior are considered where relevant.

### Tests and documentation

- [ ] Add or update Pest coverage for success, validation, authorization, tenancy, and important business rules.
- [ ] Fake external providers, queues, storage, and time where appropriate.
- [ ] Run the narrow test, affected suite, frontend lint/typecheck, and formatting relevant to the change.
- [ ] Update the applicable files in `docs/specs/` and `public/feature-docs/`.
- [ ] Mention migrations, environment variables, queues, scheduled commands, or external integration changes in the handoff.

## How to resolve conflicting patterns

EduManager contains older resource/Blade/AJAX patterns alongside newer Action, Inertia, audit, AI, and shared-component patterns. Do not normalize the whole project while implementing a feature. Prefer the pattern used by the same module and most recent neighboring code, while preserving compatibility with its existing route/page/test contracts. If a new abstraction is needed, document why it belongs in the existing layer and keep the first implementation narrow.

