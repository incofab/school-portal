import type { Locator, Page } from '@playwright/test';
import { config, pace } from '../config';
import {
  clickWithCursor,
  hrefForLinkText,
  pauseMedium,
  pauseShort,
  typeForTutorial,
} from '../helpers/tutorial-actions';
import { Tutorial } from '../helpers/tutorial';

/**
 * "EduManager Feature Overview Walkthrough" is intentionally a read-only
 * product tour. The only browser action that changes state is signing in.
 * Every feature after that is reached with GET navigation and is shown as a
 * list, overview, report, preview, or detail page. Do not add create, edit,
 * delete, submit, send, payment, result, attendance, assignment, or lesson
 * note actions to this tutorial.
 */

interface PageBeat {
  eyebrow: string;
  title: string;
  description: string;
  url: string;
  target?: Locator;
  focus?: Locator[];
  holdMs?: number;
}

function destination(baseUrl: string, path: string): string {
  return `${baseUrl}/${path.replace(/^\/+/, '')}`;
}

async function isVisible(locator: Locator): Promise<boolean> {
  return (
    (await locator.count().catch(() => 0)) > 0 &&
    (await locator
      .first()
      .isVisible()
      .catch(() => false))
  );
}

async function resolveTarget(
  page: Page,
  preferred?: Locator
): Promise<Locator> {
  const candidates = [
    preferred,
    page.locator('h1, h2, h3').first(),
    page.locator('table').first(),
    page.locator('form').first(),
    page.locator('main').first(),
  ].filter((candidate): candidate is Locator => Boolean(candidate));

  for (const candidate of candidates) {
    if (await isVisible(candidate)) return candidate.first();
  }

  return page.locator('body');
}

async function waitForDestination(page: Page): Promise<void> {
  await page.waitForLoadState('networkidle').catch(() => undefined);
  await page
    .locator('h1, h2, h3, table, form, main')
    .first()
    .waitFor({ state: 'visible', timeout: 15000 })
    .catch(() => undefined);
  await pauseShort();
}

async function showPage(
  tutorial: Tutorial,
  page: Page,
  beat: PageBeat
): Promise<void> {
  await page.goto(beat.url, { waitUntil: 'domcontentloaded' });
  await waitForDestination(page);

  const target = await resolveTarget(page, beat.target);
  if (beat.focus?.length) {
    await tutorial.focusOn(
      beat.focus.filter((field) => field).map((field) => field.first()),
      {
        maxScale: 1.3,
      }
    );
  }

  await tutorial.step({
    eyebrow: beat.eyebrow,
    title: beat.title,
    description: beat.description,
    target,
    holdBeforeActionMs: beat.holdMs ?? pace.long,
  });

  if (beat.focus?.length) {
    await tutorial.clearFocus();
  }
}

async function showOptionalDetail(
  tutorial: Tutorial,
  page: Page,
  listDescription: string,
  eyebrow: string,
  selectors: string[]
): Promise<boolean> {
  const link = page.locator(selectors.join(', ')).first();
  if (!(await isVisible(link))) return false;

  const href = await link.getAttribute('href');
  if (!href) return false;

  await showPage(tutorial, page, {
    eyebrow,
    title: 'Open a Read-Only Detail',
    description: listDescription,
    url: new URL(href, page.url()).href,
    target: page.locator('main').first(),
  });

  return true;
}

export async function runFeatureOverviewWalkthroughTutorial(
  page: Page
): Promise<void> {
  const tutorial = await Tutorial.create(page);

  // Login is the sole state-changing interaction in this tour. Everything
  // below it is direct navigation or visual highlighting only.
  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  const emailInput = page.getByLabel('Email address');
  const passwordInput = page.getByLabel('Password', { exact: true });
  const loginButton = page.getByRole('button', {
    name: 'Login',
    exact: true,
  });
  await emailInput.waitFor({ state: 'visible' });

  await tutorial.announce(
    'Tutorial',
    'EduManager Feature Overview Walkthrough',
    'Let’s take a quick, read-only tour of the places EduManager brings together for school administrators and teachers.',
    pace.long
  );

  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Enter the Demo Account',
    description:
      'Sign in to the school administrator account to begin the tour.',
    target: emailInput,
    action: async () => {
      await typeForTutorial(page, emailInput, config.demoEmail);
      await typeForTutorial(page, passwordInput, config.demoPassword);
      await clickWithCursor(page, loginButton);
    },
  });

  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  const institutionBaseUrl = page.url().replace(/\/dashboard(\/|$|\?).*$/, '');
  await page.getByRole('button', { name: 'Open menu' }).waitFor({
    state: 'visible',
    timeout: 20000,
  });
  await pauseMedium();

  // 1. Dashboard ---------------------------------------------------------
  await tutorial.announce(
    '1 of 12',
    'Your School at a Glance',
    'The dashboard is the starting point for EduManager, with quick access to people, classes, academic work, payments, results, communication, and other school operations.',
    pace.long
  );
  await showPage(tutorial, page, {
    eyebrow: 'Dashboard',
    title: 'A Central School Workspace',
    description:
      'Use the dashboard cards and the sidebar to move quickly between the parts of school management you use every day.',
    url: destination(institutionBaseUrl, 'dashboard'),
    target: page.locator('main').first(),
    holdMs: pace.long,
  });

  // 2. Students ----------------------------------------------------------
  await tutorial.announce(
    '2 of 12',
    'Student Management',
    'The Students area is where administrators see the school’s registered learners and move into related student records.',
    pace.medium
  );
  const studentsHref = await hrefForLinkText(page, 'All Students');
  await showPage(tutorial, page, {
    eyebrow: 'Students',
    title: 'Registered Students',
    description:
      'This read-only list brings together student names, classes, identification details, registration dates, and the available row actions.',
    url: new URL(studentsHref, page.url()).href,
    target: page.locator('table').first(),
    focus: [page.locator('table').first()],
  });
  await tutorial.step({
    eyebrow: 'Students',
    title: 'Search, Filter, and Follow a Record',
    description:
      'The list supports search and filters, while related student and class tools lead to profiles, guardians, payments, results, evaluations, identification outputs, and class movement workflows.',
    target: page.getByText(/Students:/).first(),
    holdBeforeActionMs: pace.long,
  });

  // 3. Fees and payments -------------------------------------------------
  await tutorial.announce(
    '3 of 12',
    'Fees and Payments',
    'Next is the financial workspace for school fees, payment activity, balances, and receipts.',
    pace.medium
  );
  const feesUrl = destination(institutionBaseUrl, 'fees');
  await showPage(tutorial, page, {
    eyebrow: 'Fees',
    title: 'The Fee List',
    description:
      'Fees are listed here with their amounts, line items, sectors, and payment intervals. The visible row controls are available for later administration, but this tour does not use them.',
    url: feesUrl,
    target: page.locator('table').first(),
    focus: [page.locator('table').first()],
  });
  const reminderControl = page
    .getByRole('row', { name: /Feature Overview Tuition Fee/ })
    .getByRole('button', { name: 'Send Reminder' });
  if (await isVisible(reminderControl)) {
    await tutorial.step({
      eyebrow: 'Fee Follow-Up',
      title: 'Send Reminder Control',
      description:
        'On a fee row, this clock-shaped control is where outstanding-payment reminders are accessed. It is highlighted only; the reminder panel is not opened.',
      target: reminderControl,
      holdBeforeActionMs: pace.long,
    });
  }
  await showPage(tutorial, page, {
    eyebrow: 'Payments',
    title: 'Payment Records and Summaries',
    description:
      'Payment records show activity and totals, with filters for reviewing what has been paid. Receipts and student payment history are available from the related payment areas when records exist.',
    url: destination(institutionBaseUrl, 'fee-payments/index'),
    target: page.locator('table').first(),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Receipts',
    title: 'Receipt History',
    description:
      'The receipts area provides a read-only place to review payment receipts and open a receipt detail when one is available.',
    url: destination(institutionBaseUrl, 'receipts'),
    target: page.locator('table').first(),
  });

  // 4. Attendance --------------------------------------------------------
  await tutorial.announce(
    '4 of 12',
    'Attendance',
    'Attendance brings daily presence, class registers, check-in and check-out records, and student reports into one area.',
    pace.medium
  );
  await showPage(tutorial, page, {
    eyebrow: 'Attendance',
    title: 'Attendance Records',
    description:
      'Review the attendance list and its filters without changing any record.',
    url: destination(institutionBaseUrl, 'attendances'),
    target: page.locator('table').first(),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Attendance',
    title: 'Attendance Recording View',
    description:
      'This is the staff-facing recording page. It is shown for orientation only; no class, student, status, or submit control is used.',
    url: destination(institutionBaseUrl, 'attendances/create'),
    target: page.locator('form').first(),
    focus: [page.locator('form').first()],
  });
  await showPage(tutorial, page, {
    eyebrow: 'Attendance',
    title: 'Class Attendance Register',
    description:
      'The class register gives staff a class-level view of attendance for a selected date, ready for a normal working session later.',
    url: destination(institutionBaseUrl, 'attendances/class-register/view'),
    target: page.getByText('Class Attendance Register', { exact: true }),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Attendance Reports',
    title: 'Student Attendance Report',
    description:
      'Use this report to review a student’s attendance over the academic period without updating the underlying records.',
    url: destination(institutionBaseUrl, 'attendance-reports'),
    target: page.getByText('Student Attendance Report', { exact: true }),
  });

  // 5. Results ------------------------------------------------------------
  await tutorial.announce(
    '5 of 12',
    'Results and Score Management',
    'The result system connects course scores, class analysis, term results, session results, reports, student details, review, and publication.',
    pace.long
  );
  await showPage(tutorial, page, {
    eyebrow: 'Course Results',
    title: 'Course Result Pages',
    description:
      'Course result pages organize subject-level result records and provide the starting point for reviewing academic performance.',
    url: destination(institutionBaseUrl, 'course-result-info/index'),
    target: page.locator('table').first(),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Class Results',
    title: 'Class Result Analysis',
    description:
      'Class result analysis brings a class’s academic picture together for review. Calculation, locking, and other controls are intentionally left unused.',
    url: destination(institutionBaseUrl, 'class-result-info/index'),
    target: page.getByText('Class Result Analysis', { exact: true }),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Term Results',
    title: 'Term Results',
    description:
      'Term results provide a student-level view of grades, totals, positions, comments, and related result details when records are present.',
    url: destination(institutionBaseUrl, 'term-results/index'),
    target: page.getByText('Term Results', { exact: true }),
  });
  await showOptionalDetail(
    tutorial,
    page,
    'When a result row is present, its Result Detail link opens the student result record for review without editing it.',
    'Student Result Details',
    ['a[title="Result Detail"]', 'a[title="View"]']
  );
  await showPage(tutorial, page, {
    eyebrow: 'Session Results',
    title: 'Session Results',
    description:
      'Session results collect the academic picture across the session and can lead to a read-only result sheet when a record is available.',
    url: destination(institutionBaseUrl, 'session-results/index'),
    target: page.getByText(/Session Results/).first(),
  });
  await showOptionalDetail(
    tutorial,
    page,
    'A Result Sheet link opens the selected session result as a report-style view for reading or printing.',
    'Session Result Sheet',
    ['a[title="Result Sheet"]']
  );
  await showPage(tutorial, page, {
    eyebrow: 'Result Reports',
    title: 'Reports and Publication Review',
    description:
      'Report pages and the Result Publications area give administrators a place to review report outputs and publication records. No result is calculated, locked, transferred, or published here.',
    url: destination(institutionBaseUrl, 'result-publications'),
    target: page.getByText('Result Publications', { exact: true }),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Report Sheets',
    title: 'A Result Report Preview',
    description:
      'This preview shows how a result sheet is presented to a viewer, including the school identity, learner details, subject results, totals, and comments when available.',
    url: destination(institutionBaseUrl, 'result-sheets/dummy'),
    target: page.locator('main').first(),
    holdMs: pace.long,
  });

  // 6. Result settings and templates ------------------------------------
  await tutorial.announce(
    '6 of 12',
    'Result Templates and Settings',
    'Result settings control visible report preferences, while the preview link shows the selected report-sheet presentation.',
    pace.long
  );
  await showPage(tutorial, page, {
    eyebrow: 'Result Settings',
    title: 'Choose How Results Are Presented',
    description:
      'The settings page visibly groups result preferences such as position display, template, and exam-result display. This tour leaves the current choices unchanged.',
    url: destination(institutionBaseUrl, 'settings/create'),
    target: page.getByText('Result Setting', { exact: true }),
    focus: [
      page.getByText('Result Setting', { exact: true }),
      page.getByText('Preview Template', { exact: true }),
    ],
    holdMs: pace.long,
  });

  // 7. Messages and communication ---------------------------------------
  await tutorial.announce(
    '7 of 12',
    'Messages and Communication',
    'Communication tools help schools reach students, guardians, staff, or groups through the available message and notification surfaces.',
    pace.medium
  );
  await showPage(tutorial, page, {
    eyebrow: 'Messages',
    title: 'Message History',
    description:
      'The message list is the place to review communication history. Nothing is opened or sent during this tour.',
    url: destination(institutionBaseUrl, 'messages/index'),
    target: page.getByText('List Messages', { exact: true }),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Messages',
    title: 'Message Composition View',
    description:
      'This page shows the available recipient, channel, subject, and message areas. It is displayed without typing, selecting recipients, or submitting anything.',
    url: destination(institutionBaseUrl, 'messages/create'),
    target: page.getByText('Send Messages', { exact: true }),
    focus: [page.locator('form').first()],
  });
  await showPage(tutorial, page, {
    eyebrow: 'Sent Notifications',
    title: 'Sent Notification Records',
    description:
      'Review sent notification records here without opening a conversation or sending a new notification.',
    url: destination(institutionBaseUrl, 'notifications/sent'),
    target: page.locator('table').first(),
  });

  // 8. Fee reminders -----------------------------------------------------
  await tutorial.announce(
    '8 of 12',
    'Fee Reminders',
    'Fee reminders are accessed from the fee-management list, making it easy to follow up on outstanding balances when a school is ready to communicate.',
    pace.medium
  );
  await showPage(tutorial, page, {
    eyebrow: 'Fee Follow-Up',
    title: 'Where Reminders Live',
    description:
      'Return to the fee list to find the Send Reminder control alongside a fee row when it is available. This control is pointed out only; no reminder modal is opened.',
    url: feesUrl,
    target: reminderControl,
  });

  // 9. Assignments -------------------------------------------------------
  await tutorial.announce(
    '9 of 12',
    'Assignments and Submissions',
    'Assignments give teachers a place to organize student work, instructions, due dates, and submitted responses.',
    pace.medium
  );
  await showPage(tutorial, page, {
    eyebrow: 'Assignments',
    title: 'Assignment List',
    description:
      'The assignment list provides an overview of teaching work and exposes a read-only View path when an assignment exists.',
    url: destination(institutionBaseUrl, 'assignments'),
    target: page.getByText('List of Assignments', { exact: true }),
  });
  const assignmentSubmissionsHref = await page
    .getByRole('link', { name: 'Submissions' })
    .first()
    .getAttribute('href');
  if (!assignmentSubmissionsHref) {
    throw new Error('The seeded assignment did not expose a submissions link.');
  }
  await showOptionalDetail(
    tutorial,
    page,
    'The View link opens assignment instructions and due-date details without entering an answer or changing the assignment.',
    'Assignment Details',
    ['a[title="View"]']
  );
  await showPage(tutorial, page, {
    eyebrow: 'Submissions',
    title: 'Submitted Assignments',
    description:
      'This list is where teachers review submitted work. A submission detail is opened only when the current read-only list provides one.',
    url: new URL(assignmentSubmissionsHref, page.url()).href,
    target: page.getByText('Submitted Assignments', { exact: true }),
  });
  await showOptionalDetail(
    tutorial,
    page,
    'A submission View link opens the submitted work for review, without scoring or changing it.',
    'Submission Details',
    ['a[title="View"]']
  );

  // 10. Curriculum ------------------------------------------------------
  await tutorial.announce(
    '10 of 12',
    'Lesson Plans, Notes, and Curriculum',
    'The curriculum section keeps teaching content structured across schemes of work, lesson plans, lesson notes, and topics.',
    pace.medium
  );
  await showPage(tutorial, page, {
    eyebrow: 'Curriculum',
    title: 'Schemes of Work',
    description:
      'Schemes of work provide the broader teaching structure that lesson plans and notes can build on.',
    url: destination(institutionBaseUrl, 'scheme-of-works'),
    target: page.locator('table').first(),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Lesson Plans',
    title: 'Lesson Plan List',
    description:
      'Lesson plans organize what a teacher intends to teach. The list can lead to a read-only plan detail where records exist.',
    url: destination(institutionBaseUrl, 'lesson-plans'),
    target: page.getByText('Lesson Plans', { exact: true }),
  });
  await showOptionalDetail(
    tutorial,
    page,
    'The View link opens a lesson plan’s structured detail, without editing it or adding teaching material.',
    'Lesson Plan Details',
    ['a[title="View"]']
  );
  await showPage(tutorial, page, {
    eyebrow: 'Lesson Notes',
    title: 'Lesson Note List',
    description:
      'Lesson notes document what was taught and can keep supporting teaching material together for future reference.',
    url: destination(institutionBaseUrl, 'lesson-notes'),
    target: page.getByText('Lesson Notes', { exact: true }),
  });
  await showOptionalDetail(
    tutorial,
    page,
    'A lesson note View link opens the note as a read-only detail page when one is available.',
    'Lesson Note Details',
    ['a[title="View"]']
  );
  await showPage(tutorial, page, {
    eyebrow: 'Curriculum',
    title: 'Topics and Supporting Structure',
    description:
      'Topics provide another way to keep teaching content organized across subjects and plans.',
    url: destination(institutionBaseUrl, 'inst-topics'),
    target: page.locator('table').first(),
  });

  // 11. Optional supporting areas ---------------------------------------
  await tutorial.announce(
    '11 of 12',
    'A Few Supporting Areas',
    'A few shorter stops complete the tour: classes, subjects, reports, and school settings.',
    pace.medium
  );
  await showPage(tutorial, page, {
    eyebrow: 'Classes',
    title: 'Classes and Class Groups',
    description:
      'Classes and class groups provide the structure used by student lists, attendance, fees, and academic results.',
    url: destination(institutionBaseUrl, 'classifications'),
    target: page.locator('table').first(),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Subjects',
    title: 'Subjects and Teacher Assignments',
    description:
      'Subjects are maintained here, with related teacher-assignment tools available from the same academic area.',
    url: destination(institutionBaseUrl, 'courses'),
    target: page.locator('table').first(),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Reports',
    title: 'Academic Reports',
    description:
      'Report pages turn the school’s academic records into focused views for subjects, classes, grades, and individual review.',
    url: destination(institutionBaseUrl, 'reports/grade-report'),
    target: page.locator('form').first(),
  });

  // 12. CBT Exams --------------------------------------------------------
  await tutorial.announce(
    '12 of 12',
    'CBT Exams',
    'EduManager also gives institutions a place to set up computer-based tests for internal school exams, admission exams, and recruitment tests.',
    pace.long
  );
  await showPage(tutorial, page, {
    eyebrow: 'CBT Exams',
    title: 'CBT Event Overview',
    description:
      'The CBT Events area is where exam events and their connected subjects or question content are reviewed. No exam is created or started in this tour.',
    url: destination(institutionBaseUrl, 'events'),
    target: page.getByText('List Events', { exact: true }),
    holdMs: pace.long,
  });
  await showOptionalDetail(
    tutorial,
    page,
    'When an event is present, its View path opens the exam overview. An active exam interface is shown only if the existing read-only records make one available.',
    'CBT Exam Overview',
    ['a[title="View"]']
  );

  await tutorial.announce(
    'Conclusion',
    'One Workspace for the School Day',
    'From students and attendance to results, payments, communication, curriculum, and CBT exams, EduManager keeps the school’s daily operations connected. This walkthrough stayed read-only so you could see where everything lives before using the tools in a real working session.',
    pace.long
  );
  await tutorial.dismiss();
}
