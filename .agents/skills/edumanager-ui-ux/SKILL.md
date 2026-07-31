---
name: edumanager-ui-ux
description: Audit, redesign, and polish EduManager user interfaces built with Laravel, Inertia, React, TypeScript, and Chakra UI. Use for UI/UX improvement, responsiveness, accessibility, navigation, forms, tables, dashboards, dialogs, empty/loading/error states, visual consistency, or modernizing an existing page. Preserve business logic, routes, API contracts, permissions, data structures, and established workflows unless the user explicitly requests functional changes. Do not use for backend-only tasks with no user-facing impact.
---

# EduManager UI/UX Enhancement Skill

Improve EduManager so that each screen is clean, intuitive, responsive, accessible, visually consistent, and efficient for everyday school operations.

Treat this as a production education-management platform used by administrators, school owners, teachers, bursars, parents, students, and support staff. Optimize for users who may be busy, non-technical, using small screens, or working with slow networks.

## Core mandate

Enhance the interface without changing the meaning or outcome of the existing application logic.

Always preserve, unless the user explicitly requests otherwise:

- Laravel routes and route names.
- Existing API endpoints, request payloads, and response contracts.
- Database behaviour and relationships.
- Authentication, authorization, roles, permissions, and tenancy boundaries.
- Form field names and backend validation contracts.
- Existing calculations, reports, result processing, payments, and academic workflows.
- Existing redirects, success conditions, and error semantics.
- Public component APIs used elsewhere in the application.

A UI improvement must not silently become a product-logic rewrite.

## Technology context

Assume the main stack is:

- Laravel backend.
- React with TypeScript.
- Inertia.js.
- Chakra UI.
- Vite.

Before changing code, inspect the repository and follow the actual versions, patterns, utilities, design tokens, layouts, hooks, and shared components already in use. Do not introduce a second styling system when Chakra UI is already the project standard.

## When this skill should activate

Use this skill when the task includes any of the following:

- Improve or redesign an existing page, component, form, table, dashboard, modal, drawer, wizard, or navigation flow.
- Fix an interface that feels crowded, outdated, confusing, inconsistent, or difficult to use.
- Improve responsiveness for mobile, tablet, laptop, and large desktop screens.
- Improve accessibility, readability, hierarchy, feedback, discoverability, or task completion.
- Standardize visual styles across multiple pages.
- Audit the frontend for UI/UX problems.
- Add loading, empty, success, warning, offline, or error states.
- Reduce excessive clicks, scrolling, duplicated controls, or unclear terminology.
- Modernize the visual appearance without altering the underlying workflow.

Do not activate for backend-only refactoring unless the change directly supports a user-facing experience.

## Non-negotiable principles

### 1. Understand before redesigning

Do not begin with cosmetic edits.

First determine:

- Who uses the screen.
- What primary task they are trying to complete.
- What information they must see first.
- Which action is primary, secondary, destructive, or rare.
- Which states can occur: loading, empty, partial, error, disabled, read-only, success, and permission-restricted.
- Whether the same pattern already exists elsewhere in EduManager.

Trace the relevant React component, Inertia props, Laravel controller response, routes, form submission, validation errors, and shared layout before editing.

### 2. Preserve user familiarity

Prefer progressive improvement over disruptive reinvention.

Keep familiar labels, paths, and workflows unless they are demonstrably confusing. If a significant layout change is necessary, retain the same task sequence and make the new hierarchy obvious.

### 3. One clear purpose per screen

Every page must communicate:

- Where the user is.
- What the page is for.
- What the user can do next.
- What has already happened.
- Whether their action succeeded or failed.

The dominant action must be easy to identify. Avoid multiple competing primary buttons.

### 4. Design for real operational data

Do not design only for ideal demo content. Test mentally and, where practical, visually with:

- Very long student, parent, class, subject, and institution names.
- Large datasets.
- Missing optional values.
- Validation errors.
- Slow requests.
- Empty records.
- Narrow screens.
- Users with restricted permissions.
- Long academic-session or term labels.

### 5. Accessibility is part of quality

Aim for WCAG 2.2 AA-level usability.

Ensure:

- Semantic headings are ordered correctly.
- Inputs have visible labels, not placeholder-only labels.
- Interactive elements are keyboard accessible.
- Focus states remain visible.
- Icon-only actions have accessible labels and tooltips where helpful.
- Colour is not the only way status or errors are communicated.
- Text and controls have sufficient contrast.
- Click and tap targets are comfortably sized.
- Dialog focus is managed and returned correctly.
- Error messages are associated with their fields.
- Motion is restrained and does not block task completion.

### 6. Responsive behaviour must be intentional

Do not merely shrink desktop layouts.

For each changed screen, explicitly handle:

- Small mobile screens.
- Large mobile and small tablet screens.
- Tablet and compact laptop screens.
- Standard desktop screens.
- Wide screens.

Use Chakra responsive props and existing breakpoints. Avoid fixed widths that create horizontal overflow.

On narrow screens:

- Stack form controls logically.
- Keep the primary action reachable.
- Convert overly wide tables into a responsive pattern such as priority columns, cards, horizontal scrolling with clear affordance, or a details drawer.
- Avoid hiding essential information without providing another way to access it.
- Keep dialogs within the viewport and allow their content to scroll.
- Prevent action buttons from becoming tiny or wrapping unpredictably.

## Design-system rules

### Visual hierarchy

Use a consistent hierarchy:

1. Page title and brief context.
2. Primary action or key status.
3. Filters, search, or supporting controls.
4. Main content.
5. Secondary details and low-priority actions.

Avoid using large headings, heavy borders, shadows, and bright colours everywhere. Emphasis loses meaning when everything is emphasized.

### Spacing

Use a small, repeatable spacing scale based on the existing Chakra theme. Prefer consistent gaps over arbitrary margins.

As a general pattern:

- Use compact spacing inside tightly related controls.
- Use medium spacing between related sections.
- Use larger spacing between distinct page regions.
- Avoid nesting multiple padded cards that waste screen space.

### Typography

- Use the project’s existing font and type scale.
- Keep body text readable.
- Use weight and size to show hierarchy, not decoration.
- Avoid excessive uppercase text.
- Keep labels concise and action-oriented.
- Break long explanations into helper text or contextual guidance.

### Colour

Use semantic design tokens rather than hard-coded colour values whenever possible.

Reserve colours consistently:

- Primary brand colour for main actions and active navigation.
- Green or success token for completed or successful states.
- Yellow/orange or warning token for attention-needed states.
- Red or danger token for destructive actions and errors.
- Neutral tones for structure, borders, backgrounds, and secondary text.

Do not introduce random colours to make a page “beautiful.” Consistency is more valuable than novelty.

### Surfaces and borders

- Prefer clean surfaces with subtle borders.
- Use shadows sparingly for elevation, not decoration.
- Avoid excessive cards around every element.
- Keep border radius consistent with the theme.
- Use dividers only where grouping is unclear without them.

### Icons

- Use the icon library already present in the repository.
- Pair unfamiliar icons with text.
- Do not use an icon when a clear text label is more understandable.
- Keep icon size and stroke weight consistent.

## Component-specific requirements

### Page headers

A standard page header should normally contain:

- Clear page title.
- Optional one-sentence description or contextual metadata.
- Breadcrumb or back navigation only when it improves orientation.
- One primary action.
- Secondary actions grouped separately, preferably in a menu when numerous.

Do not place unrelated controls in the page header.

### Navigation

- Clearly show the active location.
- Group related modules using terminology already understood by school users.
- Avoid deep, surprising navigation hierarchies.
- Keep important daily actions easy to reach.
- Collapse navigation predictably on small screens.
- Do not remove navigation items solely to reduce visual clutter; reorganize them responsibly.

### Forms

For every form:

- Group fields by user intent, not database structure.
- Use clear labels and short helper text where ambiguity exists.
- Mark required fields consistently.
- Use suitable input types and controls.
- Show validation messages next to the relevant fields.
- Preserve entered values after validation failures.
- Disable submission only when necessary and show a progress state during submission.
- Prevent accidental duplicate submission.
- Keep Save/Create/Update actions easy to find.
- Separate destructive actions from normal form actions.
- Use collapsible “advanced” sections only for genuinely infrequent fields.
- Do not hide required fields in collapsed sections.

For long forms, consider sections, step indicators, sticky action areas, or accordions only when they improve comprehension.

### Tables and data lists

Tables must support real administrative work.

Ensure:

- Columns are prioritized by importance.
- Headers remain understandable.
- Long values wrap or truncate with a way to reveal the full value.
- Numeric values align consistently.
- Statuses use both text and visual indicators.
- Row actions are predictable and not overcrowded.
- Bulk actions appear only when selection is supported.
- Search, filter, sorting, pagination, and result counts are presented clearly when available.
- Empty states explain why no records are shown and what the user can do next.
- Loading states do not resemble empty states.
- Tables remain usable on small screens.

Do not add client-side sorting or filtering that contradicts backend pagination or query behaviour.

### Dashboards

- Lead with the information needed for decisions, not decorative metrics.
- Use concise cards for important totals and trends.
- State the period or scope of every metric.
- Avoid charts when a number or small table communicates the information better.
- Never fabricate trend data.
- Make cards clickable only when there is a meaningful destination.
- Keep dashboard density manageable.

### Dialogs, drawers, and popovers

- Use dialogs for focused decisions or short forms.
- Use drawers for contextual detail or longer side tasks when appropriate.
- Do not place complex multi-section pages inside small modals.
- Give destructive confirmations explicit consequences.
- Label buttons with the actual action, such as “Delete student,” rather than “Yes.”
- Allow cancel and close actions without ambiguity.
- Prevent accidental dismissal when data loss is possible, or warn before discarding changes.

### Notifications and feedback

Every user action should produce suitable feedback.

- Use inline validation for field problems.
- Use a toast or status message for successful actions.
- Use an error alert with a helpful next step for failed actions.
- Do not expose raw server exceptions to users.
- Preserve enough technical detail in logs for developers.
- For long-running operations, show progress or an honest processing state.
- Avoid showing repeated success toasts for automatic background actions.

### Loading states

Use the least disruptive loading pattern:

- Button spinner for a button-triggered action.
- Skeleton for initial page or card loading.
- Inline spinner for a small dependent region.
- Progress indicator for a measurable long-running operation.

Do not block the entire page when only one small section is updating.

### Empty states

A useful empty state should state:

- What is missing.
- Why it may be missing.
- What the user can do next.

Include a primary action only when the user has permission and the action is relevant.

### Error states

- Explain the problem in human language.
- Preserve user input where possible.
- Offer retry, correction, or navigation to safety.
- Distinguish permission errors, validation errors, network failures, missing records, and server failures.
- Do not imply that an action succeeded when its final status is unknown.

## EduManager-specific UX priorities

Pay particular attention to these recurring platform areas:

- Institution and academic-session context must always be clear.
- Current term, class, section, and subject context must not be ambiguous.
- Result-entry and result-processing screens must minimize mistakes.
- Payment and fee screens must clearly show amount, status, date, student, session, and outstanding balance.
- Student and staff records must make identity and status easy to verify.
- Permission-sensitive actions must be hidden or disabled consistently according to existing authorization rules.
- Impersonation must be visually obvious and safely reversible.
- Print and PDF-oriented pages must retain correct A4 behaviour and must not inherit screen-only controls.
- Bulk operations must clearly state scope and consequence before execution.
- Destructive academic actions must require explicit confirmation.
- Offline or syncing workflows must distinguish pending, syncing, synced, failed, and retryable states.

## Required workflow

Follow these steps for each UI/UX task.

### Step 1: Inspect

- Locate the target route, page, components, layout, hooks, types, controller, Inertia response, and validation flow.
- Identify shared components or patterns that should be reused.
- Check whether the issue appears elsewhere and whether a shared fix is safer than a page-specific patch.
- Review the current mobile and desktop structure.
- Identify business logic that must remain untouched.

### Step 2: State the UX problem

Before editing, write a concise internal problem statement containing:

- Primary user.
- Primary task.
- Current friction.
- Desired improvement.
- Logic and contracts that must be preserved.

Do not produce a redesign that lacks a clear user problem.

### Step 3: Choose the smallest effective design change

Prefer, in order:

1. Reuse an existing good pattern.
2. Improve a shared component.
3. Refactor the target page presentation.
4. Add a new reusable component when repetition justifies it.
5. Add a dependency only when the repository lacks the capability and the benefit clearly outweighs the cost.

Do not add new UI libraries, icon packages, animation libraries, or state libraries without a strong reason.

### Step 4: Implement cleanly

- Keep components focused and readable.
- Extract reusable presentation components when useful, but avoid premature abstraction.
- Preserve types and strengthen them where safe.
- Avoid `any` unless the repository already requires it and no practical type is available.
- Reuse Chakra theme tokens and shared components.
- Avoid inline magic values.
- Keep responsive logic close to the component it affects.
- Do not duplicate server state into local state unnecessarily.
- Keep Inertia form behaviour consistent with existing project patterns.

### Step 5: Verify every state

Check at least:

- Normal populated state.
- Empty state.
- Loading/submitting state.
- Validation error state.
- Server/network error state where applicable.
- Permission-restricted state.
- Small-screen state.
- Large-screen state.
- Long-content state.

### Step 6: Run relevant checks

Use the repository’s actual commands. At minimum, run the most relevant available checks such as:

- TypeScript type checking.
- ESLint or frontend linting.
- Frontend production build.
- Targeted automated tests.
- Laravel tests when the UI change touches request or response behaviour.
- Formatting checks.

Do not claim a check passed unless it was actually run successfully.

### Step 7: Review the diff

Before finishing:

- Confirm no route, API, permission, validation, or business-rule change slipped in unintentionally.
- Remove dead code, commented experiments, unused imports, and debug logging.
- Confirm the changed interface still follows neighbouring project conventions.
- Check that mobile improvements did not harm desktop use.
- Check that visual polish did not reduce information clarity.

## Guardrails

Never do the following unless explicitly requested:

- Change API response shapes for visual convenience.
- Rename routes or form fields.
- Alter database schemas.
- Replace authorization checks with frontend-only hiding.
- Change financial, academic, grading, attendance, or result calculations.
- Remove user-visible information because the layout is crowded.
- Introduce a new design system beside Chakra UI.
- Rewrite an entire module when a targeted improvement is sufficient.
- Add animations that delay work or distract users.
- Use placeholder data in production components.
- Hard-code institution-specific values into shared components.
- Hide errors without providing feedback.
- mark unfinished requests as successful.

## Decision rules for ambiguity

When a design decision is not specified:

- Follow existing EduManager patterns if they are usable and consistent.
- Prefer clarity over novelty.
- Prefer fewer, better-organized controls over many visible actions.
- Prefer explicit labels over clever icons.
- Prefer responsive composition over fixed pixel dimensions.
- Prefer progressive disclosure for rare options.
- Prefer safe defaults that preserve current behaviour.
- Prefer a small reversible change over a broad redesign.

Ask a question only when a missing decision would materially affect product behaviour, brand identity, or a destructive workflow. Otherwise, make a reasonable, conservative choice and document it.

## Definition of done

A UI/UX task is complete only when:

- The primary task is easier to understand and complete.
- Visual hierarchy is clear.
- The page works across relevant screen sizes.
- Loading, empty, error, success, and validation states are handled.
- Accessibility has improved or at least not regressed.
- Existing business logic and contracts remain intact.
- Repeated patterns use shared components or tokens where appropriate.
- The code passes the relevant available checks.
- The final response clearly states what changed, what was intentionally preserved, and what verification was performed.

## Required completion report

At the end of the task, report:

1. UX problems addressed.
2. Main interface changes.
3. Responsive and accessibility improvements.
4. Files changed.
5. Business logic and contracts preserved.
6. Checks run and their results.
7. Any remaining limitation that could not be verified.

Keep this report specific. Do not describe unimplemented improvements as completed.

## Example prompts that should invoke this skill

- “Use the EduManager UI/UX skill to redesign this student list without changing its logic.”
- “Improve this result-entry page for mobile and reduce user mistakes.”
- “Audit all React pages for inconsistent spacing, tables, forms, and dialogs.”
- “Modernize this dashboard using the existing Chakra UI theme.”
- “Make this fee-payment workflow clearer while preserving the current API.”
- “Fix the responsiveness and accessibility of this Inertia page.”
- “Create a consistent empty, loading, and error-state pattern across EduManager.”

## Example prompts that should not invoke this skill

- “Optimize this Laravel queue worker.”
- “Fix a MySQL deadlock in result processing.”
- “Add a new API endpoint with no frontend work.”
- “Rewrite the grading algorithm.”

Those tasks may require a backend, architecture, performance, or reliability skill instead.
