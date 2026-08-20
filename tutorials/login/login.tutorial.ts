import type { Page } from '@playwright/test';
import { config, pace } from '../config';
import {
  clickWithCursor,
  pauseMedium,
  pauseShort,
  typeForTutorial,
} from '../helpers/tutorial-actions';
import { Tutorial } from '../helpers/tutorial';

/**
 * Guided "how to log in" walkthrough. Narrates and demonstrates: opening the
 * login page, entering credentials, signing in, landing on the dashboard,
 * and logging back out. Written for a first-time viewer, not a test runner
 * — no assertion/test language is ever shown on screen.
 */
export async function runLoginTutorial(page: Page): Promise<void> {
  const tutorial = await Tutorial.create(page);

  const emailInput = page.getByLabel('Email address');
  const passwordInput = page.getByLabel('Password', { exact: true });
  const loginButton = page.getByRole('button', { name: 'Login', exact: true });

  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  // The login form being visible/usable is the real "page ready" signal —
  // more reliable than matching page heading text.
  await emailInput.waitFor({ state: 'visible' });
  await pauseMedium();

  await tutorial.announce(
    'Tutorial',
    'How to Log In',
    "Let's walk through how to access your account.",
    pace.long
  );

  // The login card is the whole subject of the next few steps — zoom in
  // once and stay there instead of zooming per field, so it reads clearly
  // even on a small screen.
  await tutorial.focusOn([emailInput, passwordInput, loginButton]);

  await tutorial.step({
    eyebrow: 'Step 1 of 3',
    title: 'Enter Your Email',
    description: 'Enter the email address associated with your account.',
    target: emailInput,
    action: async () => {
      await typeForTutorial(page, emailInput, config.demoEmail);
    },
  });

  await tutorial.step({
    eyebrow: 'Step 2 of 3',
    title: 'Enter Your Password',
    description: 'Enter your account password.',
    target: passwordInput,
    action: async () => {
      await typeForTutorial(page, passwordInput, config.demoPassword);
    },
  });

  await tutorial.step({
    eyebrow: 'Step 3 of 3',
    title: 'Click Login',
    description: 'Click "Login" to continue.',
    target: loginButton,
    action: async () => {
      await clickWithCursor(page, loginButton);
    },
  });

  await tutorial.clearFocus();
  await tutorial.dismiss();

  // Robust, condition-based wait for the authenticated state — not an
  // arbitrary timeout. The dashboard route always ends in /dashboard, and
  // the account menu (labelled with the signed-in user's name) only renders
  // once the authenticated layout has mounted.
  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  // Chakra's Menu sets aria-label="Open menu" on this button, which is the
  // element's real accessible name (it takes precedence over the "Demo"
  // text it displays), so that's what we match on.
  const accountMenuButton = page.getByRole('button', { name: 'Open menu' });
  await accountMenuButton.waitFor({ state: 'visible', timeout: 20000 });
  await pauseMedium();

  await tutorial.announce(
    'Success',
    'Login Successful',
    "You're now logged in. This is your dashboard, where you can access the features available to your account.",
    pace.long
  );

  await tutorial.announce(
    'Next Step',
    'Logging Out',
    'When you’re finished, use the account menu to securely log out.',
    pace.medium
  );

  // Small, corner-of-the-screen interaction — a light zoom here still
  // helps a mobile viewer see exactly where to click.
  await tutorial.focusOn([accountMenuButton], { maxScale: 1.4 });
  await clickWithCursor(page, accountMenuButton);
  await pauseShort();

  const logoutItem = page.getByRole('menuitem', { name: 'Logout' });
  await logoutItem.waitFor({ state: 'visible' });
  // Re-fit now that the menu (and Logout) is visible, so both the button
  // and the item it opened stay framed together.
  await tutorial.focusOn([accountMenuButton, logoutItem], { maxScale: 1.4 });

  await tutorial.step({
    eyebrow: 'Logging Out',
    title: 'Select Logout',
    description: 'Click Logout to securely sign out of your account.',
    target: logoutItem,
    action: async () => {
      await clickWithCursor(page, logoutItem);
    },
  });

  await tutorial.clearFocus();

  await page.waitForURL(/\/login(\/|$|\?)/, { timeout: 20000 });
  await emailInput.waitFor({ state: 'visible', timeout: 20000 });
  await pauseMedium();

  await tutorial.announce(
    'All Done',
    'Tutorial Complete',
    'You now know how to log in and securely log out of your account.',
    pace.long
  );

  await tutorial.dismiss();
}
