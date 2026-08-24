import fs from 'node:fs';
import path from 'node:path';
import type { Page } from '@playwright/test';
import { config } from './config';
import { runArtisanCommand } from './helpers/artisan';
import { startRecording } from './helpers/tutorial-recorder';

interface TutorialEntry {
  load: () => Promise<(page: Page) => Promise<void>>;
  /**
   * Artisan command (and args) that creates/updates whatever fixture data
   * this tutorial needs before recording (a class, a demo student, etc).
   * Runs after the database snapshot is taken, so anything it does is
   * automatically undone by the restore at the end of `main()` — no
   * tutorial needs to reset or clean up after itself. Omit for tutorials
   * that only need the shared demo admin/institution (see
   * `EnsureTutorialInstitution`).
   */
  seedArtisanCommand?: string[];
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
    seedArtisanCommand: ['tutorial:seed-demo-user'],
  },
  'fee-payment': {
    load: async () =>
      (await import('./fee-payment/fee-payment.tutorial'))
        .runFeePaymentTutorial,
    seedArtisanCommand: ['tutorial:seed-fee-demo'],
  },
  'cbt-exam-workflow': {
    load: async () =>
      (await import('./cbt-exam-workflow/cbt-exam-workflow.tutorial'))
        .runCbtExamWorkflowTutorial,
    seedArtisanCommand: ['tutorial:seed-cbt-demo'],
  },
  'school-onboarding-walkthrough': {
    load: async () =>
      (
        await import(
          './school-onboarding-walkthrough/school-onboarding-walkthrough.tutorial'
        )
      ).runSchoolOnboardingWalkthroughTutorial,
    seedArtisanCommand: ['tutorial:seed-onboarding-demo'],
  },
  'feature-overview-walkthrough': {
    load: async () =>
      (
        await import(
          './feature-overview-walkthrough/feature-overview-walkthrough.tutorial'
        )
      ).runFeatureOverviewWalkthroughTutorial,
    seedArtisanCommand: ['tutorial:seed-feature-overview-demo'],
  },
  'result-recording-workflow': {
    load: async () =>
      (
        await import(
          './result-recording-workflow/result-recording-workflow.tutorial'
        )
      ).runResultRecordingWorkflowTutorial,
    seedArtisanCommand: ['tutorial:seed-result-recording-workflow-demo'],
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

  // Snapshot the whole database before anything else runs, so the restore
  // in `finally` below can put it back exactly as it was regardless of
  // what this tutorial creates or modifies — no per-tutorial cleanup code
  // needed. See app/Actions/Tutorials/CreateDatabaseSnapshot.php.
  console.log('[tutorial] Creating database snapshot...');
  await runArtisanCommand(['tutorial:db-snapshot']);

  try {
    if (entry.seedArtisanCommand) {
      console.log('[tutorial] Preparing tutorial data...');
      await runArtisanCommand(entry.seedArtisanCommand);
    }

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
    }
  } finally {
    // Always restore, whether the tutorial succeeded or failed above —
    // this is what keeps a failed/aborted run from leaving the database
    // polluted with partial demo data.
    console.log('[tutorial] Restoring database snapshot...');
    await runArtisanCommand(['tutorial:db-restore']).catch((error) => {
      console.error('[tutorial] restore failed:', error);
      process.exitCode = 1;
    });
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
