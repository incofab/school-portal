# Frontend conventions

## Bootstrap and page resolution

`resources/js/app.tsx` creates the Inertia app, resolves `resources/js/pages/**/*.tsx` with `import.meta.glob`, and mounts React 18 under `StrictMode`, `ColorModeScript`, `ChakraProvider`, and `ProSidebarProvider`. `InertiaProgress` is initialized with a white progress color.

The Inertia component name is a slash-separated path without the `.tsx` extension, for example `institutions/students/list-students`. Page files are lowercase/kebab-case even though exported React components commonly use PascalCase. Preserve the actual page naming convention in the target area.

`resources/views/app.blade.php` provides Ziggy routes, Vite assets, the Inertia root, and `window.AppProps.institutionGroup`, which is used to select the runtime brand palette.

TypeScript is strict, has the `@/*` alias to `resources/js/*`, and uses ESLint rules that reject `console`, non-strict equality, unused variables, and Prettier violations. Prettier uses single quotes and two-space indentation.

## Layouts and shared props

- Institution pages normally render inside `DashboardLayout`.
- Manager pages normally render inside `ManagerDashboardLayout`.
- Both shells include a responsive Pro Sidebar, the shared `DashboardHeader`, a content area, and an impersonation banner when active.
- `useSharedProps()` reads `HandleInertiaRequests` props such as current user, current institution, current institution user, permissions, academic session/term, settings, unread notifications, and impersonation state.
- `useInstitutionRoute()` prefixes named routes with `institutions.` and the current institution UUID. Use it for institution links rather than manually concatenating URLs.
- `util/route.ts` wraps the Ziggy `window.route` function for non-institution routes.

`HandleInertiaRequests` eagerly loads institution classifications/courses/groups/divisions and current institution roles/permissions. It also shares current academic-session data and current term. A page that needs additional props should return them from its controller; do not duplicate shared props in every controller.

## Forms and mutation flow

The dominant form abstraction is `useWebForm` plus Axios. It tracks `data`, `errors`, and `processing`, maps HTTP 422 errors to field errors, and returns `{ ok, data, errors, message }`. Shared controls include `FormControlBox`, `InputForm`, `ReactSelectControl`, `UserInputForm`, selector components, `FormButton`, and `BrandButton`.

Common sequence:

```text
useWebForm(initial data)
  -> submit((data, web) => web.post/put/delete(named route, data))
  -> useMyToast().handleResponseToast(result)
  -> Inertia.visit(...) or Inertia.reload({ only: [...] })
```

Only a small number of pages use Inertia's `useForm` directly. `axios` and `useWebForm` are therefore the default to inspect first, while preserving a neighboring page's established pattern.

Use `preventNativeSubmit` for Chakra form wrappers where the page follows the existing pattern. Respect `form.processing` with `isLoading` and show field errors through shared controls.

## Tables, filters, and URL state

- `ServerPaginatedTable` is the default for server-paginated lists. It supports nested value access, custom renderers, optional local search, URL-driven sorting, previous/next pagination, horizontal scrolling, and a filter button.
- `DataTable` is for in-memory arrays and local search.
- Table filter components live in `components/table-filters`; `BaseTableFilter` renders a right-side Chakra Drawer and persists filters with Inertia while preserving state.
- `useQueryString` and utility functions keep filter state in the URL. A filter button displays the number of active recognized filters and offers clear-filter behavior.
- Many lists use `Slab`, `SlabHeading`, and `SlabBody` as the visual wrapper.

When adding a list, first find a neighboring page with the same pagination/filter needs. Keep query keys, server filter classes, table `validFilters`, and page prop names synchronized.

## Reusable UI inventory

Look in `resources/js/components` before creating a new component. The most broadly reused groups are:

- `buttons.tsx`, `page-header.tsx`, `slab.tsx`, `semantic.tsx`, centered layouts, and date display.
- Form controls, password input, TinyMCE, file dropper, media attachment list, and selectors.
- `server-paginated-table.tsx`, `data-table.tsx`, filter controls, and Excel export.
- `generic-modal.tsx`, `use-modal-toggle.tsx`, domain modals, and `destructive-popover.tsx`.
- `permission-gate.tsx` and role/permission hooks.
- Dashboard stats/charts, result helpers, ID cards, activity logs, and payment/bank-account components.

Use `useModalToggle` for a boolean modal and `useModalValueToggle<T>` when the modal needs an entity. Use `PermissionGate` for UI visibility, but never treat it as the backend authorization boundary.

## Navigation and reloads

Use `InertiaLink` for normal named links, `Inertia.visit` for programmatic visits, and `Inertia.reload({ only: [...] })` when a mutation should refresh selected props. Use ordinary anchors for downloads or external/public URLs when that is how the page currently works.

Pages commonly show success/error feedback with Chakra toasts through `useMyToast`. Destructive changes use `DestructivePopover` or a purpose-built modal and refresh the affected list after success.

## Types and data shape

Shared model shapes live in `types/models.ts`, common values in `types/types.ts`, and permissions in `types/permissions.ts`. Page-specific props are commonly declared next to the page. Match the serialized Eloquent relationship names (`institution_user`, `course_teacher`, etc.) returned by Laravel rather than silently renaming them on the client.

