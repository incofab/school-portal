# EduManager engineering specifications

Status: checked against the repository on 2026-09-26.

This directory is the maintainers' engineering reference for the current EduManager codebase. It describes what is implemented and the conventions that future work should follow. It is deliberately evidence-led: paths and examples point to the code that establishes the behavior.

## Source-of-truth order

When sources disagree, use this order:

1. Running code and migrations in this checkout.
2. Tests that exercise the running code.
3. `AGENTS.md`, `CLAUDE.md`, and other repository instructions.
4. `public/feature-docs/`, which is a useful feature inventory but may lag behind newly added code.
5. This specification set, which should be corrected when the code changes.

The specifications do not authorize architectural redesign. If a behavior is unclear, inspect the nearest route, controller, model, page, and test before extending it.

## Reading map

| File | Use it for |
| --- | --- |
| [01-architecture.md](01-architecture.md) | Runtime shape, route groups, request lifecycle, and layer boundaries |
| [02-backend-conventions.md](02-backend-conventions.md) | Laravel routes, controllers, Actions, requests, services, responses, jobs, and backend implementation rules |
| [03-frontend-conventions.md](03-frontend-conventions.md) | Inertia/React structure, pages, props, forms, navigation, tables, hooks, and reuse |
| [04-ui-system.md](04-ui-system.md) | Chakra theme, layout, visual language, responsive behavior, feedback, and print conventions |
| [05-data-domain.md](05-data-domain.md) | Tenancy hierarchy, important relationships, migrations, soft deletes, settings, results, and finance data |
| [06-security-access.md](06-security-access.md) | Authentication, institution boundaries, roles, permissions, policies, impersonation, API access, and audit logging |
| [07-module-map.md](07-module-map.md) | Feature areas and their route/controller/action/model/page/test entry points |
| [08-cross-cutting-workflows.md](08-cross-cutting-workflows.md) | Traceable route-to-UI workflows, files, payments, messaging, AI, filtering, and reporting |
| [09-testing-operations.md](09-testing-operations.md) | Pest setup, factories, seeders, test strategy, queues, schedules, configuration, and tutorials |
| [10-codex-development-guide.md](10-codex-development-guide.md) | The practical checklist for making future changes consistently |

## Repository map at a glance

```text
HTTP request
  routes/*.php
    middleware + route model binding
      app/Http/Controllers
        Form Requests / Policies / inline controller guards
          Actions, Services, Support, Query Builders
            Eloquent Models + database migrations
          Inertia::render(...) or JSON/file response
        resources/js/pages/**/*.tsx
          shared layouts, components, hooks, utilities
```

The main web application is a Laravel monolith. Inertia connects Laravel-rendered page props to React pages; it is not a separate frontend API. JSON endpoints also exist for AJAX-style operations, API clients, webhooks, offline mocks, and public/exam flows.

## Existing documentation relationship

`public/feature-docs/index.html`, `features.html`, `backend.html`, `frontend.html`, and `data.html` remain the existing browser-readable feature knowledge base. `docs/specs/` is the maintainable Markdown engineering specification layer. A feature change that alters behavior, routes, data, or UI should update both the relevant Markdown specification and the affected `public/feature-docs/` page, as required by the repository instructions.

## Maintenance rules

- Record observed behavior, not desired product behavior.
- Link to source paths rather than copying large source blocks.
- Name both sides when a pattern has legacy and newer variants.
- Keep module entries tied to actual routes, classes, pages, and tests.
- Update the relevant spec in the same change as a route, model, workflow, permission, or reusable UI change.
- Do not add a dependency, state-management layer, repository layer, or UI system merely because this document mentions that the project does not currently use one.

