import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { config } from '../config';

const execFileAsync = promisify(execFile);

/**
 * Shells out to `artisan` (see `config.artisanCommand`) from mid-tutorial.
 * Used sparingly — currently only by the fee-payment tutorial to invoke
 * `tutorial:simulate-monnify-payment` once the real Monnify sandbox
 * checkout has been demonstrated on screen (see that command's docblock
 * for why a real bank transfer can't be simulated any other way).
 */
export async function runArtisanCommand(args: string[]): Promise<string> {
  const [cmd, ...baseArgs] = config.artisanCommand.split(' ');

  const { stdout, stderr } = await execFileAsync(cmd, [...baseArgs, ...args], {
    cwd: config.projectRoot,
  });

  if (stderr.trim()) {
    console.error(`[artisan] ${stderr.trim()}`);
  }
  if (stdout.trim()) {
    console.log(`[artisan] ${stdout.trim()}`);
  }

  return stdout;
}
