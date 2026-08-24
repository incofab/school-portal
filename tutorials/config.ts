import path from 'node:path';

export type TutorialDevice = 'desktop' | 'mobile';

// Common 16:9 tutorial-video size for the big-screen walkthroughs.
const desktopViewport = { width: 1440, height: 900 };

// A representative modern phone size (iPhone 13/14-class portrait) for the
// mobile walkthroughs. Mobile recordings never zoom in/out — the app's
// responsive layout already renders at a readable size on a screen this
// small, so `Tutorial.focusOn()` is a no-op whenever this device is active
// (see helpers/tutorial.ts).
const mobileViewport = { width: 390, height: 844 };

// Set TUTORIAL_DEVICE=mobile to record the phone-sized version of a
// tutorial instead of the desktop one — see tutorials/README.md.
export const deviceType: TutorialDevice =
  process.env.TUTORIAL_DEVICE === 'mobile' ? 'mobile' : 'desktop';
export const isMobile = deviceType === 'mobile';
export const viewport = isMobile ? mobileViewport : desktopViewport;

// Pause presets used throughout tutorials so pacing stays consistent and
// easy to tune globally. See tutorials/README.md for guidance on when to
// reach for which one.
export const pace = {
  short: 650, // between 500-800ms — small beats, e.g. after removing a highlight
  medium: 1300, // between 1000-1500ms — default "let the viewer read this" pause
  long: 2500, // between 2000-3000ms — narration screens (intro/success/outro)
};

export const config = {
  baseUrl: (process.env.TUTORIAL_BASE_URL ?? 'http://localhost').replace(
    /\/+$/,
    ''
  ),
  demoEmail: process.env.TUTORIAL_USER_EMAIL ?? 'tutorial@example.com',
  demoPassword: process.env.TUTORIAL_USER_PASSWORD ?? 'password',
  // Headed by default so the tutorial can be watched/debugged as it runs.
  // Set TUTORIAL_HEADLESS=true for CI-style unattended generation.
  headless: process.env.TUTORIAL_HEADLESS === 'true',
  deviceType,
  isMobile,
  viewport,
  outputDir: path.resolve(__dirname, '..', 'public', 'tutorials'),
  debugDir: path.resolve(__dirname, '..', 'public', 'tutorials', 'debug'),
  projectRoot: path.resolve(__dirname, '..'),
  // The base command used to shell out to `artisan` mid-tutorial (see
  // helpers/artisan.ts). This project's local dev DB is only reachable
  // through Sail, so set TUTORIAL_ARTISAN_COMMAND="./vendor/bin/sail artisan"
  // when generating locally — see tutorials/README.md.
  artisanCommand: process.env.TUTORIAL_ARTISAN_COMMAND ?? 'php artisan',
};

// Fixture data seeded by `php artisan tutorial:seed-fee-demo`
// (app/Console/Commands/SeedFeeTutorialData.php) — keep these in sync with
// that command's constants.
export const feeTutorialFixtures = {
  studentCode: 'TUT0001',
  guardianEmail: 'tutorial.guardian@example.com',
  feeTitle: 'PTA Development Levy',
  className: 'JSS 1',
  bankName: 'Access bank',
  bankAccountNumber: '0000000000',
};

// Fixture data seeded by `php artisan tutorial:seed-cbt-demo`
// (app/Console/Commands/Tutorials/SeedCbtTutorialData.php) — keep these in
// sync with that command's constants.
export const cbtTutorialFixtures = {
  studentCode: 'TUT0002',
  className: 'JSS 3',
  classGroupName: 'CBT Demo Group',
  courseTitle: 'CBT Demo Subject',
  sessionName: 'CBT Practice Session',
  eventTitle: 'CBT Demo Exam',
};

// Fixture data seeded by `php artisan tutorial:seed-onboarding-demo`
// (app/Console/Commands/Tutorials/SeedOnboardingTutorialData.php) — keep
// these in sync with that command's constants. Unlike the other tutorials'
// fixtures, this seed command creates nothing beyond the admin/institution
// themselves — every value below is instead typed into the app live, by
// the tutorial script, so the recording shows a genuinely empty school
// being set up from scratch.
export const onboardingTutorialFixtures = {
  adminEmail:
    process.env.TUTORIAL_ONBOARDING_USER_EMAIL ??
    'tutorial.onboarding.admin@example.com',
  institutionName: 'New Horizon Academy',
  classGroupTitle: 'Junior Secondary',
  classTitles: ['JSS 1', 'JSS 2'],
  subjectTitles: ['Mathematics', 'English Language'],
  teacherFirstName: 'Grace',
  teacherLastName: 'Adeyemi',
  teacherEmail: 'tutorial.onboarding.teacher@example.com',
  teacherPhone: '08033333333',
  studentFirstName: 'David',
  studentLastName: 'Okafor',
  studentPhone: '08022222222',
  guardianPhone: '08011111111',
  guardianFirstName: 'Blessing',
  guardianLastName: 'Okafor',
  guardianEmail: 'tutorial.onboarding.guardian@example.com',
  bankName: 'Access bank',
  bankAccountNumber: '0000000001',
};
