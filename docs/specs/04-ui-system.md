# UI system and interaction patterns

## Brand and theme

The runtime Chakra theme is created in `resources/js/app.tsx`. The institution group brand color is read from `window.AppProps.institutionGroup.brand_color` and mapped through `resources/js/util/color-util.tsx`. Available palettes are `edumanager`, `red`, `orange`, `yellow`, `green`, `blue`, `purple`, and `pink`; the default EduManager palette is green, with `main` `#2a8864`, `500` `#36af81`, `600` `#2a8864`, `700` `#1e6148`, and dark `900` `#06130e`.

The theme starts in light mode and does not follow the system color mode. The header toggles light/dark mode. Chakra's `brand.*` tokens should be preferred over one-off brand colors.

## Shell and composition

The standard authenticated shell is a two-column layout: responsive dark sidebar plus a content column with a white/dark header and a pale `brand.50`/dark-gray page background. The sidebar collapses at the Pro Sidebar `lg` breakpoint. The shared header provides:

- sidebar toggle;
- current term/session display for institution pages;
- optional dashboard guide;
- theme toggle;
- unread notification badge/link;
- institution chats link when applicable;
- profile, change-password, and logout menu.

`Slab` is the primary panel/card wrapper: rounded border, light/dark surface, internal padding, and subtle shadow. `SlabHeading` places a medium page title and optional actions above a divider. `PageTitle`, `BrandButton`, `LinkButton`, and `FormButton` keep repeated headings and actions consistent.

## Typography, spacing, and color usage

The codebase uses Chakra defaults with mostly `sm`/`md` headings, `VStack`/`HStack` spacing, responsive `px`, `py`, `fontSize`, and `SimpleGrid` declarations. Text is typically gray/muted on surfaces, with `brand` for primary actions and status colors such as red, orange, blue, teal, and green for meaning.

Do not invent a second token system. Prefer existing Chakra props and shared primitives. When a one-off visual is justified, keep light and dark values paired with `useColorModeValue` as neighboring pages do.

## Forms

Forms generally use a vertical Chakra stack with `FormControlBox` labels and error messages, selectors, and a brand submit button. Create/edit screens often use `CenteredBox` or a `Slab` with a constrained width. Multi-section forms use dividers and centered section labels.

Use visible labels, `isRequired` where the server requires a value, `isInvalid` through the shared form controls, and `isLoading` while requests are processing. Preserve server-side validation as the source of truth.

## Lists, tables, and filters

Lists typically combine a `SlabHeading` action row with `ServerPaginatedTable` and a filter Drawer. Tables use small Chakra table sizing, custom renderers for links/actions, icon buttons for compact row actions, horizontal scrolling where needed, and a “No results found” row for local tables.

Use `Menu` for multiple row actions, `DestructivePopover` for irreversible deletion, and filter badges/counts through `FilterButton`. Do not hide important actions only in hover states; maintain the existing icon button labels and accessible names.

## Modals and drawers

`GenericModal` is a simple shared shell, while most business flows use purpose-built components under `components/modals`. Modal state is local and controlled through `useModalToggle`/`useModalValueToggle`. Filter panels use a right-side Drawer with Cancel and Filter actions.

Modals should close on success or explicitly reload the affected Inertia props, show request progress, and use the same toast/error conventions as full pages.

## Dashboards and feedback states

Dashboards use role-aware cards and stats, `SimpleGrid`, alerts for attention items, and responsive column definitions such as `{ base: 1, sm: 2, lg: 4 }`. The institution dashboard adapts cards and attention items to institution role and setup state; the manager dashboard adapts to manager/partner role and partner analytics.

Observed feedback patterns include Chakra toast success/error messages, inline field errors, disabled/loading buttons, empty table rows, attention alerts, spinners in file/assistant areas, and route-level error pages. Add loading/empty/error behavior consistent with the nearest existing surface.

## Responsive and print behavior

Mobile behavior is implemented through Chakra responsive props, wrapping `HStack`/`Wrap`, `SimpleGrid` breakpoints, constrained text widths, sidebar collapse, and horizontal table scrolling. Check a mobile-width layout whenever adding a row of actions, a table, a dashboard grid, or a modal form.

Print-specific behavior exists in `resources/js/style/app.css` and `domain/institutions/page-print-layout.tsx`: print colors are preserved, `.hidden-on-print` is hidden, `.show-on-print` is shown, and ID-card/QR-card surfaces remove screen shadows. Result sheets, receipts, transcripts, and ID cards are presentation-critical; keep print CSS and page-specific templates intact.

## Accessibility baseline observed in code

Icon buttons generally provide `aria-label`, interactive controls use Chakra components, and assistant/navigation regions include labels. Continue this practice for new icon-only actions, preserve visible form labels, and ensure keyboard focus is not removed. This is a continuation of existing implementation behavior, not a new accessibility framework.

