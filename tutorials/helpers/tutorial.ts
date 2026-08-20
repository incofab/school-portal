import type { Locator, Page } from '@playwright/test';
import { config, pace } from '../config';
import {
  applyZoom,
  computeFitTransform,
  highlightElement,
  pause,
  removeHighlight,
  resetZoom,
  zoomToElement,
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
   * every input/select/button in a form card — and keeps that same zoom
   * level active for subsequent `step()` calls (each just *pans* to its
   * own target instead of zooming out and back in). Use this whenever a
   * form, modal, or dense card is the main subject of the next few steps,
   * so small-screen/mobile viewers can actually read it; skip it for wide
   * table/list pages where zooming would just crop columns.
   *
   * Safe to call again with a new set of fields while already focused
   * (e.g. once a dynamically-added row appears) — it just re-fits.
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

  /** Ends the current `focusOn` region and zooms back out to normal. */
  async clearFocus(): Promise<void> {
    if (this.focusScale === null) return;

    this.focusScale = null;
    await resetZoom(this.page);
    await pause(pace.short);
  }

  /** A single guided interaction: show explanation, highlight, act, clean up. */
  async step(opts: TutorialStepOptions): Promise<void> {
    await showOverlayCard(
      this.page,
      opts.eyebrow,
      opts.title,
      opts.description
    );

    if (opts.target) {
      if (this.focusScale !== null) {
        // Same scale as the active focus region — this pans the already
        // zoomed view onto the new target rather than zooming out and in.
        await zoomToElement(this.page, opts.target, this.focusScale);
        await pause(pace.short);
      }
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
