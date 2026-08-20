import path from 'node:path';
import type { Page } from '@playwright/test';
import './types';

const RUNTIME_SCRIPT_PATH = path.join(__dirname, 'browser-runtime.js');

/**
 * Installs the on-screen narration/highlight/cursor runtime on a page.
 * Uses `addInitScript` so it survives full page reloads (this app does a
 * hard `window.location.href` navigation after login), not just SPA
 * transitions.
 */
export async function installTutorialOverlay(page: Page): Promise<void> {
  await page.addInitScript({ path: RUNTIME_SCRIPT_PATH });
}

/** Best-effort page.evaluate — swallows errors from mid-navigation races. */
async function safeEvaluate<Args>(
  page: Page,
  fn: (args: Args) => void,
  args: Args
): Promise<void> {
  try {
    // Playwright's PageFunction typing can't prove Args is already
    // "unboxed" (JSON-serializable) generically — it is, for every call
    // site in this file, which all pass plain data objects.
    await page.evaluate(fn as (arg: unknown) => void, args);
  } catch {
    // A hard navigation (e.g. after clicking Login) can destroy the
    // execution context mid-call. The overlay is cosmetic, so we just skip
    // this frame rather than fail the whole tutorial.
  }
}

export async function showOverlayCard(
  page: Page,
  eyebrow: string,
  title: string,
  description: string
): Promise<void> {
  await safeEvaluate(
    page,
    ({ eyebrow, title, description }) =>
      window.__tutorialRuntime?.showCard(eyebrow, title, description),
    { eyebrow, title, description }
  );
}

export async function hideOverlayCard(page: Page): Promise<void> {
  await safeEvaluate(page, () => window.__tutorialRuntime?.hideCard(), {});
}

export async function hideCursor(page: Page): Promise<void> {
  await safeEvaluate(page, () => window.__tutorialRuntime?.hideCursor(), {});
}
