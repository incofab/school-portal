import fs from 'node:fs';
import path from 'node:path';
import type { Page } from '@playwright/test';
import { config } from './config';
import { runArtisanCommand } from './helpers/artisan';
import { startRecording } from './helpers/tutorial-recorder';

interface TutorialEntry {
  load: () => Promise<(page: Page) => Promise<void>>;
  /**
   * Artisan command (and args) to shell out to once the recording ends —
   * success or failure — to wipe any DB records the run itself created, so
   * nothing it generated is left sitting around afterwards. Omit for
   * tutorials that don't write any lasting demo data (e.g. login).
   */
  cleanupArtisanCommand?: string[];
}

/**
 * Registry of available tutorials. To add a new one:
 *   1. Write tutorials/<name>/<name>.tutorial.ts exporting an async
 *      `run(page)` function (see tutorials/login/login.tutorial.ts).
 *   2. Register it here.
 *   3. Optionally add an `npm run tutorial:<name>` script.
 */
const TUTORIALS: Record<string, TutorialEntry> = {
  login: {
    load: async () => (await import('./login/login.tutorial')).runLoginTutorial,
  },
  'fee-payment': {
    load: async () =>
      (await import('./fee-payment/fee-payment.tutorial'))
        .runFeePaymentTutorial,
    // The tutorial creates a fee, a bank account, and payments while it
    // records — clear them back out afterwards so re-running (or just
    // watching it happen) never leaves demo data sitting in the database.
    // See app/Console/Commands/Tutorials/ClearFeeTutorialData.php.
    cleanupArtisanCommand: ['tutorial:clear-fee-demo'],
  },
};

async function main(): Promise<void> {
  const name = process.argv[2];
  const entry = name ? TUTORIALS[name] : undefined;

  if (!entry) {
    console.error(
      `Usage: npm run tutorial:<name>\n` +
        `Available tutorials: ${Object.keys(TUTORIALS).join(', ')}`
    );
    process.exitCode = 1;
    return;
  }

  console.log(
    `[tutorial] Generating "${name}" (${config.deviceType}) against ${config.baseUrl} ...`
  );

  const runTutorial = await entry.load();
  const session = await startRecording(name);

  try {
    await runTutorial(session.page);

    const { webmPath, mp4Path } = await session.finish();
    console.log(`[tutorial] Saved: ${webmPath}`);
    if (mp4Path) {
      console.log(`[tutorial] Saved: ${mp4Path}`);
    }
  } catch (error) {
    console.error(`[tutorial] "${name}" failed:`, error);
    await saveDebugScreenshot(name, session.page);
    await session.discard();
    process.exitCode = 1;
  } finally {
    if (entry.cleanupArtisanCommand) {
      await runArtisanCommand(entry.cleanupArtisanCommand).catch((error) =>
        console.error('[tutorial] cleanup failed:', error)
      );
    }
  }
}

async function saveDebugScreenshot(name: string, page: Page): Promise<void> {
  try {
    fs.mkdirSync(config.debugDir, { recursive: true });
    const screenshotPath = path.join(
      config.debugDir,
      `${name}-failure-${Date.now()}.png`
    );
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.error(`[tutorial] Saved failure screenshot: ${screenshotPath}`);
  } catch (screenshotError) {
    console.error(
      '[tutorial] Could not save a failure screenshot:',
      screenshotError
    );
  }
}

main();
