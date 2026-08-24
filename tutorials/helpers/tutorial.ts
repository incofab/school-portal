import type { Locator, Page } from '@playwright/test';
import { config, pace } from '../config';
import {
  applyZoom,
  computeFitTransform,
  highlightElement,
  pause,
  removeHighlight,
  resetZoom,
} from './tutorial-actions';
import {
  hideCursor,
  hideOverlayCard,
  installTutorialOverlay,
  showOverlayCard,
} from './tutorial-overlay';

export interface TutorialStepOptions {
  /** Small label above the title, e.g. "Step 1 of 3". */
  eyebrow: string;
  title: string;
  description: string;
  /** Element to draw a highlight ring around while this step is shown. */
  target?: Locator;
  /** Performs the actual interaction (typing, clicking, etc). */
  action?: () => Promise<void>;
  /** How long to let the card/highlight sit before running `action`. */
  holdBeforeActionMs?: number;
  /** How long to pause after `action` completes, before moving on. */
  holdAfterMs?: number;
}

/**
 * Reusable orchestration for a guided, narrated Playwright walkthrough.
 * Combines the on-screen narration card, element highlighting, zoom/focus,
 * and pacing helpers into a small step-based API so new tutorials can be
 * built the same way as the login tutorial without re-deriving this
 * plumbing.
 */
export class Tutorial {
  /** Active zoom scale while a `focusOn` region is open, else null. */
  private focusScale: number | null = null;

  private constructor(private readonly page: Page) {
    // A full page load (this app does several — a hard window.location.href
    // after login, this tutorial's own page.goto() calls, etc.) destroys the
    // zoomed <body> along with the rest of the old document. Reset our
    // in-memory scale to match reality so the next step() doesn't try to
    // "pan" a zoom that no longer exists.
    page.on('framenavigated', (frame) => {
      if (frame === page.mainFrame()) {
        this.focusScale = null;
      }
    });
  }

  static async create(page: Page): Promise<Tutorial> {
    await installTutorialOverlay(page);
    return new Tutorial(page);
  }

  /** A narration-only beat with no target element (intro/result/outro screens). */
  async announce(
    eyebrow: string,
    title: string,
    description: string,
    holdMs: number = pace.long
  ): Promise<void> {
    await showOverlayCard(this.page, eyebrow, title, description);
    await pause(holdMs);
  }

  /**
   * Zooms in once on the region spanning every locator in `fields` — e.g.
   * every input/select/button in a form card — and keeps that exact frame
   * (position and scale) locked for every subsequent `step()` call: the
   * whole form stays fully visible and the camera does not move again,
   * only the highlight ring moves between fields. Use this whenever a
   * form, modal, or dense card is the main subject of the next few steps,
   * so small-screen/mobile viewers can actually read it; skip it for wide
   * table/list pages where zooming would just crop columns.
   *
   * Pass every field/button that later `step()` calls in this region will
   * target — `step()` deliberately does not re-frame per field, so a
   * target outside this set would be highlighted off-screen.
   *
   * Safe to call again with a new set of fields while already focused —
   * e.g. once a dynamically-added row appears, a modal opens, or the
   * tutorial genuinely moves on to a different part of the same form. This
   * re-fits (pans/rescales) once for the new region, then holds still
   * again. Don't call it between every field of the *same* still-visible
   * group — that reintroduces the zoom-hopping this method exists to
   * avoid.
   *
   * No-op on mobile recordings (`TUTORIAL_DEVICE=mobile`): the phone-sized
   * viewport already renders the app's responsive layout at a readable
   * size, so zooming would only crop content — see `config.isMobile`.
   */
  async focusOn(
    fields: Locator[],
    opts?: { padding?: number; minScale?: number; maxScale?: number }
  ): Promise<void> {
    if (config.isMobile) return;

    const fit = await computeFitTransform(this.page, fields, opts);
    if (!fit) return;

    this.focusScale = fit.scale;
    await applyZoom(this.page, fit);
    await pause(pace.medium);
  }

  /**
   * Ends the current `focusOn` region and zooms back out to normal.
   *
   * Only call this once the form interaction it covered has actually
   * concluded (e.g. right after a Submit/Save action) — not between the
   * individual fields of a form that's still being filled in. Call it even
   * when the concluding action navigates elsewhere: most navigation in
   * this app is an Inertia SPA transition (`Inertia.visit`/`web.post(...)`
   * followed by a visit), which patches the page in place rather than
   * loading a new document, so the zoomed `<body>` transform would
   * otherwise carry over, framed on content that no longer matches the new
   * page. A hard navigation (`window.location.href`, a plain `<a>` to a
   * non-Inertia route, `page.goto()`) destroys the old document anyway and
   * resets the in-memory scale for free (see the `framenavigated` listener
   * in the constructor) — calling this first there is harmless, just an
   * extra beat before the page changes.
   */
  async clearFocus(): Promise<void> {
    if (this.focusScale === null) return;

    this.focusScale = null;
    await resetZoom(this.page);
    await pause(pace.short);
  }

  /**
   * A single guided interaction: show explanation, highlight, act, clean
   * up. Deliberately does not move or rescale the camera even while a
   * `focusOn` region is active — the highlight ring is enough to draw the
   * eye to `target` within the already-framed form, which is what keeps
   * the whole form visible and the view still across a run of steps. Call
   * `focusOn()` again first if the tutorial is genuinely moving to a
   * different region of the page.
   */
  async step(opts: TutorialStepOptions): Promise<void> {
    await showOverlayCard(
      this.page,
      opts.eyebrow,
      opts.title,
      opts.description
    );

    if (opts.target) {
      await highlightElement(this.page, opts.target);
    }

    await pause(opts.holdBeforeActionMs ?? pace.medium);

    if (opts.action) {
      await opts.action();
    }

    if (opts.target) {
      await removeHighlight(this.page);
    }

    await pause(opts.holdAfterMs ?? pace.short);
  }

  /** Fades out the narration card and cursor — call before ending a recording. */
  async dismiss(): Promise<void> {
    await this.clearFocus();
    await hideOverlayCard(this.page);
    await hideCursor(this.page);
    await pause(pace.short);
  }
}
