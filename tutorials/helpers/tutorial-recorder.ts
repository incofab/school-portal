import { execFile } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { promisify } from 'node:util';
import {
  type Browser,
  type BrowserContext,
  type Page,
  chromium,
} from '@playwright/test';
import { config } from '../config';

const execFileAsync = promisify(execFile);

export interface RecordingResult {
  webmPath: string;
  mp4Path: string | null;
}

export interface RecordingSession {
  page: Page;
  /** Finalizes the recording, saves it into public/tutorials, and closes the browser. */
  finish(): Promise<RecordingResult>;
  /** Aborts the recording (used on failure) and closes the browser without saving output. */
  discard(): Promise<void>;
}

/**
 * Launches a clean, headed Chromium browser recording video, ready for a
 * tutorial script to drive. Handles saving the final file into
 * public/tutorials/<name>-tutorial.webm (and .mp4 when ffmpeg is available)
 * so individual tutorial scripts don't need to know about any of this.
 */
export async function startRecording(
  tutorialName: string
): Promise<RecordingSession> {
  fs.mkdirSync(config.outputDir, { recursive: true });

  const videoDir = fs.mkdtempSync(
    path.join(os.tmpdir(), `edumanager-tutorial-${tutorialName}-`)
  );

  let browser: Browser | null = await chromium.launch({
    headless: config.headless,
    args: [
      `--window-size=${config.viewport.width},${config.viewport.height + 90}`,
    ],
  });

  let context: BrowserContext | null = await browser.newContext({
    viewport: config.viewport,
    recordVideo: { dir: videoDir, size: config.viewport },
  });

  const page = await context.newPage();

  async function finish(): Promise<RecordingResult> {
    if (!context || !browser) {
      throw new Error('Recording session was already finalized.');
    }

    const video = page.video();
    await context.close();
    context = null;

    const recordedPath = await video?.path();
    if (!recordedPath || !fs.existsSync(recordedPath)) {
      await browser.close();
      browser = null;
      throw new Error(
        'Tutorial recording failed: Playwright did not produce a video file.'
      );
    }

    const webmPath = path.join(
      config.outputDir,
      `${tutorialName}-tutorial${config.isMobile ? '-mobile' : ''}.webm`
    );
    fs.copyFileSync(recordedPath, webmPath);
    fs.rmSync(videoDir, { recursive: true, force: true });

    await browser.close();
    browser = null;

    const mp4Path = await tryConvertToMp4(webmPath);

    return { webmPath, mp4Path };
  }

  async function discard(): Promise<void> {
    await context?.close().catch(() => {});
    context = null;
    await browser?.close().catch(() => {});
    browser = null;
    fs.rmSync(videoDir, { recursive: true, force: true });
  }

  return { page, finish, discard };
}

async function isFfmpegAvailable(): Promise<boolean> {
  try {
    await execFileAsync('ffmpeg', ['-version']);
    return true;
  } catch {
    return false;
  }
}

/**
 * Converts the WebM recording to a widely-compatible H.264/AAC MP4. This is
 * strictly best-effort: if ffmpeg isn't installed or the conversion fails,
 * we log why and keep the WebM — we never fail the whole run over this.
 */
async function tryConvertToMp4(webmPath: string): Promise<string | null> {
  if (!(await isFfmpegAvailable())) {
    console.log(
      '[tutorial] ffmpeg not found on PATH — skipping MP4 conversion, keeping WebM only.'
    );
    return null;
  }

  const mp4Path = webmPath.replace(/\.webm$/, '.mp4');

  try {
    await execFileAsync('ffmpeg', [
      '-y',
      '-i',
      webmPath,
      '-c:v',
      'libx264',
      '-preset',
      'veryfast',
      '-crf',
      '20',
      '-pix_fmt',
      'yuv420p',
      '-c:a',
      'aac',
      '-movflags',
      '+faststart',
      mp4Path,
    ]);
    return mp4Path;
  } catch (error) {
    console.log(
      '[tutorial] MP4 conversion failed — keeping WebM only.',
      error instanceof Error ? error.message : error
    );
    return null;
  }
}
