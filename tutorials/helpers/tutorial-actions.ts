import type { Frame, Locator, Page } from '@playwright/test';
import { pace } from '../config';
import './types';

export function pause(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export const pauseShort = () => pause(pace.short);
export const pauseMedium = () => pause(pace.medium);
export const pauseLong = () => pause(pace.long);

/**
 * Reads the real `href` of a sidebar/nav link containing `linkText` without
 * clicking it. Used to resolve URLs that embed a database id we don't want
 * to hard-code (e.g. a student's own "Pay Fees" link) — this app's sidebar
 * (react-pro-sidebar) can render overlapping flyout panels that block real
 * Playwright clicks on collapsed submenu items, so reading the href and
 * navigating directly is the reliable option.
 */
export async function hrefForLinkText(
  page: Page,
  linkText: string
): Promise<string> {
  const href = await page
    .locator('a', { hasText: linkText })
    .first()
    .getAttribute('href');

  if (!href) {
    throw new Error(`Could not find a link containing text "${linkText}"`);
  }

  return href;
}

/**
 * Polls for a (possibly nested/cross-origin) frame whose URL contains
 * `urlSubstring` — e.g. a third-party payment widget's own iframe, which
 * can itself be nested another level deep. Playwright's `frameLocator`
 * chaining is unreliable across cross-origin nesting in practice; going
 * through `page.frames()` directly and using the returned `Frame`'s own
 * locator methods is the reliable approach.
 */
export async function waitForFrame(
  page: Page,
  urlSubstring: string,
  timeoutMs = 20000
): Promise<Frame> {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    const frame = page.frames().find((f) => f.url().includes(urlSubstring));
    if (frame) return frame;
    await pause(300);
  }
  throw new Error(
    `No frame with URL containing "${urlSubstring}" appeared within ${timeoutMs}ms`
  );
}

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
    // Cosmetic call — a navigation may have already torn down the context.
  }
}

/** Draws a glowing ring around `locator` without blocking clicks on it. */
export async function highlightElement(
  page: Page,
  locator: Locator
): Promise<void> {
  await locator.scrollIntoViewIfNeeded().catch(() => {});
  const box = await locator.boundingBox();
  if (!box) return;

  await safeEvaluate(
    page,
    (rect) => window.__tutorialRuntime?.setHighlight(rect),
    box
  );
}

export async function removeHighlight(page: Page): Promise<void> {
  await safeEvaluate(
    page,
    () => window.__tutorialRuntime?.clearHighlight(),
    {}
  );
}

/**
 * Zooms the real page content in around `locator` (our own overlay is
 * exempt — see browser-runtime.js) to `scale`. Calling this repeatedly with
 * the *same* scale but a different locator pans the already-zoomed view
 * (browser-runtime.js transitions `transform-origin`, not just `transform`)
 * instead of zooming out and back in — that's how `Tutorial.focusOn`/`step`
 * stay at one zoom level while moving between fields in the same form. Only
 * reach for this directly (rather than `Tutorial.focusOn`) for a genuine
 * single-element zoom outside of any form context.
 */
export async function zoomToElement(
  page: Page,
  locator: Locator,
  scale = 1.5
): Promise<void> {
  const box = await locator.boundingBox();
  if (!box) return;

  const x = box.x + box.width / 2;
  const y = box.y + box.height / 2;

  await safeEvaluate(
    page,
    ({ x, y, scale }) => window.__tutorialRuntime?.zoomToPoint(x, y, scale),
    { x, y, scale }
  );
}

export async function resetZoom(page: Page): Promise<void> {
  await safeEvaluate(page, () => window.__tutorialRuntime?.resetZoom(), {});
}

export interface FitTransform {
  x: number;
  y: number;
  scale: number;
}

/**
 * Computes one (x, y, scale) that frames every one of `locators` inside the
 * viewport, with `padding` breathing room — the basis for "zoom to the
 * whole form card" rather than one field at a time. Locators without a
 * current bounding box (not yet visible) are ignored. Returns null if none
 * of them are visible, or scale would come out below `minScale` isn't
 * reachable (caller should skip zooming rather than crop content).
 */
export async function computeFitTransform(
  page: Page,
  locators: Locator[],
  opts?: { padding?: number; minScale?: number; maxScale?: number }
): Promise<FitTransform | null> {
  const boxes = (
    await Promise.all(locators.map((l) => l.boundingBox().catch(() => null)))
  ).filter((box): box is NonNullable<typeof box> => box !== null);

  if (!boxes.length) return null;

  const viewport = page.viewportSize();
  if (!viewport) return null;

  const left = Math.min(...boxes.map((b) => b.x));
  const top = Math.min(...boxes.map((b) => b.y));
  const right = Math.max(...boxes.map((b) => b.x + b.width));
  const bottom = Math.max(...boxes.map((b) => b.y + b.height));

  const padding = opts?.padding ?? 70;
  const width = Math.max(right - left, 1);
  const height = Math.max(bottom - top, 1);

  const scaleX = (viewport.width - padding * 2) / width;
  const scaleY = (viewport.height - padding * 2) / height;

  const minScale = opts?.minScale ?? 1;
  const maxScale = opts?.maxScale ?? 1.5;
  const scale = Math.min(
    maxScale,
    Math.max(minScale, Math.min(scaleX, scaleY))
  );

  return { x: (left + right) / 2, y: (top + bottom) / 2, scale };
}

/** Applies a raw `{x, y, scale}` (see `computeFitTransform`). */
export async function applyZoom(page: Page, fit: FitTransform): Promise<void> {
  await safeEvaluate(
    page,
    ({ x, y, scale }) => window.__tutorialRuntime?.zoomToPoint(x, y, scale),
    fit
  );
}

/** Glides the fake on-screen cursor to the center of `locator`. */
export async function moveCursorTo(
  page: Page,
  locator: Locator
): Promise<void> {
  const box = await locator.boundingBox();
  if (!box) return;

  const x = box.x + box.width / 2;
  const y = box.y + box.height / 2;

  await safeEvaluate(
    page,
    ({ x, y }) => window.__tutorialRuntime?.moveCursor(x, y),
    { x, y }
  );
  await page.mouse.move(x, y, { steps: 12 }).catch(() => {});
  // Matches the CSS transition duration on .tt-cursor in browser-runtime.js.
  await pause(500);
}

/** Moves the visible cursor to `locator`, shows a click pulse, then clicks. */
export async function clickWithCursor(
  page: Page,
  locator: Locator
): Promise<void> {
  await moveCursorTo(page, locator);

  const box = await locator.boundingBox();
  if (box) {
    const x = box.x + box.width / 2;
    const y = box.y + box.height / 2;
    await safeEvaluate(
      page,
      ({ x, y }) => window.__tutorialRuntime?.clickPulse(x, y),
      { x, y }
    );
  }

  await pause(200);
  await locator.click();
}

/**
 * Clicks a field (with visible cursor movement) and types into it character
 * by character so a human viewer can see text being entered.
 */
export async function typeForTutorial(
  page: Page,
  locator: Locator,
  text: string,
  delayMs = 60
): Promise<void> {
  await clickWithCursor(page, locator);
  // Clears any pre-filled value (e.g. a numeric field defaulting to "0")
  // so typed digits don't visibly append onto it (e.g. "0" + "3000").
  await locator.fill('');
  await locator.pressSequentially(text, { delay: delayMs });
  // Hold briefly so a viewer can read what was typed before moving on.
  await pause(pace.short);
}

/**
 * Locates a react-select control (this app's `MySelect`/`EnumSelect`/
 * `DataSelect` components) by the exact text of its `FormLabel`. These
 * components render no `htmlFor`/`aria-*` association and no stable id, so
 * `getByLabel` can't find them — this walks forward in the DOM from the
 * label text to the next `.control` element instead. `labelIndex` picks
 * between duplicate label text on the page (e.g. a checkbox and a revealed
 * field that share the same word).
 */
export function reactSelectControl(
  page: Page,
  labelText: string,
  labelIndex = 0
): Locator {
  return page
    .getByText(labelText, { exact: true })
    .nth(labelIndex)
    .locator('xpath=following::div[contains(@class,"control")][1]');
}

/**
 * Opens a react-select control (see `reactSelectControl`) and picks the
 * option matching `optionMatcher`, with visible cursor movement for both
 * the control and the chosen option.
 */
export async function chooseReactSelectOption(
  page: Page,
  control: Locator,
  optionMatcher: string | RegExp
): Promise<void> {
  await clickWithCursor(page, control);
  await pause(pace.short);

  const option =
    typeof optionMatcher === 'string'
      ? page.getByRole('option', { name: optionMatcher, exact: true })
      : page.getByRole('option', { name: optionMatcher }).first();

  await option.waitFor({ state: 'visible' });
  await clickWithCursor(page, option);
}

/**
 * Clicks Chakra `Checkbox`/label text directly rather than the underlying
 * `<input>` — Chakra visually hides the real input under a styled control,
 * which blocks Playwright's normal element click; clicking the label text
 * triggers the same native label-for-input toggle a real user click would.
 */
export async function clickLabelText(
  page: Page,
  text: string,
  index = 0
): Promise<void> {
  await clickWithCursor(page, page.getByText(text, { exact: true }).nth(index));
}
