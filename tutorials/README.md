# Tutorial video generator

This is **automated user documentation**, not an end-to-end test suite. It
drives a real, headed Chromium browser through the app the way a first-time
user would experience it, narrates each step with on-screen callouts, and
saves the recording as a polished walkthrough video. It intentionally avoids
testing/assertion language on screen ("Locator found", "Test passed", etc.)
because the audience is end users, not developers.

Tutorial-generation code lives here, in `tutorials/`, separate from the
regular Pest test suite in `tests/`. Output videos are written to
`public/tutorials/`.

## How it works

```
tutorials/
  config.ts                    # base URL, demo credentials, viewport, pacing presets
  helpers/
    browser-runtime.js         # injected into the page: narration card, highlight ring, cursor, zoom
    tutorial-overlay.ts        # typed wrappers around the runtime (show/hide card, cursor)
    tutorial-actions.ts        # pauseShort/Medium/Long, highlightElement, typeForTutorial, clickWithCursor
    tutorial.ts                # Tutorial class — the reusable step API tutorials are written against
    tutorial-recorder.ts       # launches Chromium, records video, saves + optionally converts to MP4
    artisan.ts                 # shells out to `artisan` mid-tutorial (see simulate-monnify-payment below)
  login/
    login.tutorial.ts          # the "How to Log In" walkthrough script
  fee-payment/
    fee-payment.tutorial.ts    # the "Fee Recording & Payment" walkthrough script
  run.ts                       # CLI entry point / tutorial registry
app/Console/Commands/Tutorials/  # Artisan commands used only by the tutorial system, kept out of
                                  # the main Commands folder — seeding, resetting, and the online-
                                  # payment simulation helper (see below)
public/
  tutorials/
    login-tutorial.webm        # generated output (and .mp4 if ffmpeg is available)
    fee-payment-tutorial.webm  # generated output (and .mp4 if ffmpeg is available)
    debug/                     # failure screenshots only (not committed, not in the final output)
```

`tutorial-recorder.ts` handles everything infrastructural (launching the
browser, recording video, saving into `public/tutorials/`, converting to
MP4). A tutorial script only needs to describe the walkthrough itself using
the `Tutorial` class from `helpers/tutorial.ts`:

```ts
const tutorial = await Tutorial.create(page);

await tutorial.announce(
  'Tutorial',
  'How to Log In',
  "Let's walk through how to access your account."
);

await tutorial.step({
  eyebrow: 'Step 1 of 3',
  title: 'Enter Your Email',
  description: 'Enter the email address associated with your account.',
  target: emailInput,
  action: async () => {
    await typeForTutorial(page, emailInput, config.demoEmail);
  },
});
```

`tutorial.step()` shows the narration card, highlights the target element,
pauses so a viewer can read it, runs the action, then clears the highlight.
`tutorial.announce()` is for narration-only beats with no target element
(intro/result/outro screens).

## Prerequisites

1. Install Playwright's Chromium browser (once):
   ```bash
   npx playwright install chromium
   ```
2. The app must be running and reachable at the URL you'll generate against.
   This project runs locally via Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
3. Seed the deterministic tutorial/demo user (idempotent — safe to re-run):
   ```bash
   ./vendor/bin/sail artisan tutorial:seed-demo-user
   # or, if not using Sail and your shell can reach the DB directly:
   php artisan tutorial:seed-demo-user
   ```
   This creates (or updates) a single institution admin account and a
   matching "Tutorial Demo Academy" institution — see
   `app/Console/Commands/Tutorials/SeedTutorialDemoUser.php`. It never
   touches real user data and is safe to run repeatedly.
4. The fee-payment tutorial additionally needs its own fixture data (a
   class, a demo student, a demo parent/guardian, and a clean slate for
   fees/bank accounts/payments — see
   `app/Console/Commands/Tutorials/SeedFeeTutorialData.php`):
   ```bash
   ./vendor/bin/sail artisan tutorial:seed-fee-demo
   # or: php artisan tutorial:seed-fee-demo
   ```
   This resets (deletes and recreates) any fee, bank account, and payment
   state tied to the dedicated "Tutorial Demo Academy" institution so the
   video looks the same every time it's regenerated. It never touches any
   other institution's data.
5. The fee-payment tutorial also shells out to `artisan` once, mid-run, to
   finish an online payment (see "Demonstrating online payments" below —
   this needs the same DB access as the seed commands, i.e. Sail on this
   project). Point it at the right command with an env var:
   ```bash
   export TUTORIAL_ARTISAN_COMMAND="./vendor/bin/sail artisan"
   ```
   Defaults to plain `php artisan` if unset.

## Running

```bash
npm run tutorial:login
TUTORIAL_ARTISAN_COMMAND="./vendor/bin/sail artisan" npm run tutorial:fee-payment
```

Each generates `public/tutorials/<name>-tutorial.webm` (and a matching
`.mp4` too, if `ffmpeg` is on `PATH` — see below). Re-running overwrites the
previous file; there's no need to clean up old runs.

By default the browser runs **headed** (visible) so you can watch/debug it
as it runs. For unattended/CI-style generation, set `TUTORIAL_HEADLESS=true`.

### Mobile recordings

Every tutorial can also be generated at a phone-sized viewport, using the
`:mobile` npm script (or `TUTORIAL_DEVICE=mobile` directly):

```bash
npm run tutorial:login:mobile
TUTORIAL_ARTISAN_COMMAND="./vendor/bin/sail artisan" npm run tutorial:fee-payment:mobile
```

This records at a 390×844 (iPhone 13/14-class portrait) viewport instead of
the desktop 1440×900 one, and saves to
`public/tutorials/<name>-tutorial-mobile.webm` (+ `.mp4`) so it never
overwrites the desktop recording. `Tutorial.focusOn()` (see `helpers/tutorial.ts`)
automatically becomes a no-op on mobile — the app's responsive layout
already renders at a readable size on a phone-sized screen, so no per-form
zoom is needed, and zooming would just crop content instead. Tutorial
scripts don't need any device-specific code to support this; write the
walkthrough once and both sizes fall out of it.

## Configuration

Set these as real environment variables, or add them to `.env` (already
gitignored) — see `tutorials/config.ts` for the defaults:

| Variable                   | Default                | Purpose                                                                                                                              |
| -------------------------- | ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| `TUTORIAL_BASE_URL`        | `http://localhost`     | Where the app under test is running                                                                                                  |
| `TUTORIAL_USER_EMAIL`      | `tutorial@example.com` | Demo login email (also read by the seeder via `config('tutorial.demo_email')`)                                                       |
| `TUTORIAL_USER_PASSWORD`   | `password`             | Demo login password (shared by the admin, student, and guardian demo accounts)                                                       |
| `TUTORIAL_HEADLESS`        | `false`                | Set to `true` to run without a visible window                                                                                        |
| `TUTORIAL_DEVICE`          | `desktop`              | Set to `mobile` to record at a phone-sized viewport instead — see "Mobile recordings" above                                          |
| `TUTORIAL_ARTISAN_COMMAND` | `php artisan`          | Used by the fee-payment tutorial to shell out mid-run — set to `./vendor/bin/sail artisan` if your DB is only reachable through Sail |

The demo credentials are never used anywhere in application logic — only by
the `tutorial:seed-*` commands and the tutorial scripts themselves. Never
point them at a real account. The fee-payment tutorial's demo student
(`tutorial.student@example.com`, Student Id `TUT0001`) and demo guardian
(`tutorial.guardian@example.com`) are seeded by `tutorial:seed-fee-demo` —
see `app/Console/Commands/Tutorials/SeedFeeTutorialData.php` for the exact
fixture values.

**Note:** if Laravel Debugbar is enabled in your local `.env`
(`APP_DEBUG=true` typically enables it), the runtime script actively hides
it inside the recorded browser only — it never touches the real app or
other users' sessions.

## Pacing

`tutorials/config.ts` exports three pause presets used throughout scripts —
reach for whichever fits the moment rather than sprinkling raw millisecond
values everywhere:

- `pauseShort()` (~650ms) — small beats, e.g. right after removing a highlight
- `pauseMedium()` (~1300ms) — default "let the viewer read this" pause
- `pauseLong()` (~2500ms) — narration screens (intro, success, outro)

`tutorial.step()` and `tutorial.announce()` already use sensible defaults;
override with `holdBeforeActionMs` / `holdAfterMs` (on `step`) or the fourth
argument (on `announce`) only when a specific moment needs more or less time.

## On-screen overlays

`helpers/browser-runtime.js` is plain browser JS injected into every page
via `page.addInitScript`, so it survives full page reloads (this app does a
hard `window.location.href` redirect after login, not just an SPA
transition). It draws:

- a narration card (bottom-center) with an eyebrow label, title, and description
- a glowing highlight ring around the element currently being demonstrated
- an animated on-screen cursor with a click-pulse effect

All of it is `pointer-events: none` and purely visual — it never blocks real
clicks on the app, and it's never present in the shipped application (it
only exists inside the Playwright-controlled tutorial browser).

To change the wording of an existing step, edit the `title`/`description`
strings passed to `tutorial.step()`/`tutorial.announce()` in the relevant
`*.tutorial.ts` file. To change the visual design (colors, card position,
cursor shape), edit `helpers/browser-runtime.js`.

## Adding another tutorial

`tutorials/fee-payment/fee-payment.tutorial.ts` is a good second reference
alongside the login tutorial — it demonstrates a much longer, multi-role
flow across four separate logins: admin creates a fee and a bank account →
a student pays part of it online and sees the receipt → the admin reviews
that payment in Payment History → a parent/guardian signs in, sees the
same fee for their child, and pays part of what's left by bank transfer.

1. Create `tutorials/<name>/<name>.tutorial.ts` exporting an async function
   that takes a Playwright `Page` and drives the walkthrough.
2. Register it in the `TUTORIALS` map in `tutorials/run.ts`:
   ```ts
   'add-student': async () => (await import('./add-student/add-student.tutorial')).runAddStudentTutorial
   ```
3. Optionally add an npm script:
   ```json
   "tutorial:add-student": "tsx tutorials/run.ts add-student"
   ```
4. Prefer robust locators (`getByRole`, `getByLabel`, `getByPlaceholder`,
   `getByText`) over CSS selectors. If nothing reliable exists in the DOM,
   adding a `data-testid` to the relevant component is fine — it doesn't
   change application behavior.
   - This app's dropdown fields (`MySelect`/`EnumSelect`/`DataSelect`, used
     for payment interval, class pickers, bank selection, etc.) are
     `react-select` components with no `htmlFor`/stable id, so
     `getByLabel` won't find them. Use `reactSelectControl(page, label)` +
     `chooseReactSelectOption(page, control, optionMatcher)` from
     `helpers/tutorial-actions.ts` instead — see the fee-payment tutorial
     for many examples.
   - Chakra `Checkbox`/`Radio` visually hide their real `<input>` under a
     styled control, which blocks a direct Playwright click. Use
     `clickLabelText(page, text)` (clicks the label, same as a real user)
     rather than `getByRole('checkbox').check()`.
   - This app's sidebar (react-pro-sidebar) can render overlapping flyout
     panels that block real Playwright clicks on collapsed submenu links,
     even though the link is visibly on screen. Prefer navigating through
     a page's own buttons/links (e.g. a list page's "New" button) or, when
     you need a link's real URL without clicking it (e.g. a student's own
     "Pay Fees" link, which embeds their database id), use
     `hrefForLinkText(page, text)` and `page.goto()` directly.
5. If the flow needs specific seeded records (a class, a student, a fee,
   etc.), add a dedicated Artisan command under
   `app/Console/Commands/Tutorials/` (see `SeedFeeTutorialData.php`) using
   `firstOrCreate`/`updateOrCreate`, and reset any transactional data it
   creates (payments, receipts, etc.) at the start of the command so
   re-running produces the same clean recording every time. Reuse
   `App\Actions\Tutorials\EnsureTutorialInstitution` to get the shared demo
   admin/institution rather than re-deriving it. Keep every tutorial-only
   Artisan command in that `Tutorials/` folder, not the top-level
   `app/Console/Commands/`, so they don't get lost among real app commands.

The output will land at `public/tutorials/<name>-tutorial.webm` (+ `.mp4`)
automatically — `tutorial-recorder.ts` derives the filename from the name
you pass to `startRecording()`.

## MP4 conversion

If `ffmpeg` is available on `PATH`, `tutorial-recorder.ts` automatically
converts the WebM recording to an H.264/AAC MP4 (broadly compatible with
websites, WhatsApp, YouTube, etc.) alongside the WebM. If `ffmpeg` isn't
found, this is skipped with a clear log line — the WebM is still saved and
the run still succeeds. Install ffmpeg (e.g. `brew install ffmpeg` on
macOS) if you want MP4 output.

## Demonstrating online payments safely

The fee-payment tutorial's online-payment part clicks through to this app's
real Monnify checkout (`/monnify/checkout`), which loads Monnify's own
sandbox widget (test API keys — see `.env`'s `MONNIFY_*` values and
`MonnifyHelper`) and clicks "I've transferred the money" for real. **Nothing
fake is ever shown inside that widget** — no card/bank details are entered,
and no real money moves.

Here's the part worth understanding before you touch this: Monnify's
sandbox only ever reports a transaction as successful once an _actual_
(test) bank transfer lands against the one-time account it generates.
Clicking "I've transferred the money" does not, by itself, complete
it — in test mode or otherwise — so there's no way to fabricate that
transfer from inside this app. To still show a completed payment and its
receipt (which the tutorial does, on both the student's and the school's
side), the script:

1. Drives the real widget on screen (click included) so the recording is
   honest about what that step actually looks like.
2. Waits briefly for Monnify to redirect us away on its own (it won't,
   without a real transfer, but the script doesn't assume that).
3. If it didn't, calls `tutorial:simulate-monnify-payment {reference}` (see
   `app/Console/Commands/Tutorials/SimulateOnlinePaymentCompletion.php`),
   which finishes the payment using the exact same business logic the real
   callback would (`FeePaymentHandler::create`, `PaymentReference::confirmPayment`)
   — it only skips the Monnify API verification call, which cannot succeed
   without real money movement. It refuses to run outside local/testing
   environments.

Two more things worth knowing if you extend this:

- Third-party payment widgets can rewrite the page's DOM once they finish
  loading, which can wipe our injected overlay mid-narration. Don't try to
  caption the exact widget frame — let it hold on screen narration-free for
  a couple of pauses, then navigate back to a stable in-app page (e.g. the
  dashboard) before narrating what just happened.
- Always verify the configured payment keys are sandbox/test keys (e.g.
  Paystack's `sk_test_…`/`pk_test_…` prefix, Monnify's `MK_TEST_…` prefix)
  before recording — never run a payment tutorial against live credentials,
  and never adapt `tutorial:simulate-monnify-payment`'s approach for
  anything other than generating a demo video.

## Zooming in while typing

`typeForTutorial` (in `helpers/tutorial-actions.ts`) automatically zooms the
real page content in around whatever field it's about to type into — see
`zoomToPoint`/`resetZoom` in `browser-runtime.js`. This is a CSS `transform:
scale()` applied to `<body>` only; our own overlay lives on `<html>` (a
sibling of `<body>`) specifically so it's never caught up in that
transform. It resets automatically after each field, so you don't need to
call it directly — it's just part of what "typing" looks like in every
tutorial.

## Failures

If a tutorial script throws (a selector doesn't match, a wait times out,
etc.), `run.ts` saves a full-page screenshot to `public/tutorials/debug/`
for troubleshooting, discards the in-progress recording (nothing partial is
written to `public/tutorials/`), closes the browser cleanly, and exits with
a non-zero status. `public/tutorials/debug/` is gitignored — it's a
scratch space for you, not a deliverable.
