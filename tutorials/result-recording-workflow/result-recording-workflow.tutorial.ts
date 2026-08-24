import path from 'node:path';
import type { Locator, Page } from '@playwright/test';
import { config, pace } from '../config';
import {
  chooseReactSelectOption,
  clickLabelText,
  clickWithCursor,
  pauseMedium,
  pauseShort,
  typeForTutorial,
} from '../helpers/tutorial-actions';
import { Tutorial } from '../helpers/tutorial';

/**
 * "How to Record School Results" is a hands-on walkthrough. It deliberately
 * records demo scores, imports a demo class sheet, and transfers a completed
 * CBT score so administrators can see the real connected workflows.
 *
 * The runner snapshots the database before the seed command and restores it
 * after the recording. No result, upload, transfer, lock, or publication from
 * this tutorial survives the run.
 */

const tutorialClassSheet = path.join(
  config.projectRoot,
  'storage/app/tutorial-result-recording-workflow.xlsx'
);

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
  options: {
    eyebrow: string;
    title: string;
    description: string;
    url: string;
    target?: Locator;
    focus?: Locator[];
    holdMs?: number;
  }
): Promise<void> {
  await page.goto(options.url, { waitUntil: 'domcontentloaded' });
  await waitForDestination(page);

  if (options.focus?.length) {
    await tutorial.focusOn(
      options.focus.map((locator) => locator.first()),
      { maxScale: 1.3 }
    );
  }

  await tutorial.step({
    eyebrow: options.eyebrow,
    title: options.title,
    description: options.description,
    target: options.target?.first(),
    holdBeforeActionMs: options.holdMs ?? pace.long,
  });

  if (options.focus?.length) {
    await tutorial.clearFocus();
  }
}

function rowWithText(page: Page, pattern: RegExp): Locator {
  return page.getByRole('row', { name: pattern }).first();
}

function inputByLabel(page: Page, label: string): Locator {
  return page.getByRole('textbox', { name: label, exact: true }).first();
}

function formSelect(page: Page, label: RegExp): Locator {
  return page
    .locator('label')
    .filter({ hasText: label })
    .first()
    .locator(
      'xpath=following::div[contains(@class,"control") and not(contains(@class,"chakra-form-control"))][1]'
    );
}

async function chooseFullTerm(page: Page): Promise<void> {
  const fullTerm = page.getByText(/^Full\s+term$/i).first();
  if (await fullTerm.isVisible().catch(() => false)) {
    await clickWithCursor(page, fullTerm);
    await waitForDestination(page);
  }
}

async function chooseAsyncOption(
  page: Page,
  control: Locator,
  searchText: string,
  optionMatcher: RegExp
): Promise<void> {
  await clickWithCursor(page, control);
  const searchInput = control.locator('input').first();
  await searchInput.waitFor({ state: 'visible' });
  await searchInput.click();
  await searchInput.fill(searchText);
  await pauseMedium();
  const option = page.getByRole('option', { name: optionMatcher }).first();
  await option.waitFor({ state: 'visible' });
  await clickWithCursor(page, option);
}

async function openRecordMenu(
  page: Page,
  row: Locator,
  item: 'Single Student' | 'All Class Students'
): Promise<void> {
  const recordButton = row.getByRole('button', { name: 'Record Result' });
  await recordButton.waitFor({ state: 'visible' });
  await clickWithCursor(page, recordButton);
  const menuItem = page.getByRole('menuitem', { name: item, exact: true });
  await menuItem.waitFor({ state: 'visible' });
  await clickWithCursor(page, menuItem);
}

export async function runResultRecordingWorkflowTutorial(
  page: Page
): Promise<void> {
  const tutorial = await Tutorial.create(page);

  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  const emailInput = page.getByLabel('Email address');
  const passwordInput = page.getByLabel('Password', { exact: true });
  const loginButton = page.getByRole('button', { name: 'Login', exact: true });
  await emailInput.waitFor({ state: 'visible' });

  await tutorial.announce(
    'Tutorial',
    'How to Record School Results',
    'Let’s walk through four practical ways to put academic scores into EduManager: individual entry, class entry, Excel upload, and CBT transfer.',
    pace.long
  );

  await tutorial.focusOn([emailInput, passwordInput, loginButton]);
  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In to the School Workspace',
    description:
      'Use the school administrator account to access the result-recording tools.',
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

  await showPage(tutorial, page, {
    eyebrow: 'Results Area',
    title: 'Start from Subject Teachers',
    description:
      'Result entry is organized around a teacher assigned to a subject and class. The Record Result menu exposes single-student and whole-class entry paths.',
    url: `${institutionBaseUrl}/course-teachers/index`,
    target: page.getByText('Subject Teachers', { exact: true }),
  });

  const individualRow = rowWithText(
    page,
    /Result Workflow Mathematics.*JSS 2 Result Recording/
  );
  await tutorial.focusOn([individualRow], { maxScale: 1.2 });
  await tutorial.step({
    eyebrow: 'Choose a Class and Subject',
    title: 'Open the Recording Menu',
    description:
      'This row identifies the teacher, subject, and class. Open Record Result to choose the recording method.',
    target: individualRow,
    action: async () => {
      await clickWithCursor(
        page,
        individualRow.getByRole('button', { name: 'Record Result' })
      );
    },
  });

  const singleStudentMenuItem = page.getByRole('menuitem', {
    name: 'Single Student',
    exact: true,
  });
  await tutorial.step({
    eyebrow: 'Recording Methods',
    title: 'Choose Single Student',
    description:
      'Single Student opens a focused course-result form for one learner in this class and subject.',
    target: singleStudentMenuItem,
    action: async () => clickWithCursor(page, singleStudentMenuItem),
  });
  await page.waitForURL(/\/course-results\/create\//, { timeout: 20000 });
  const individualUrl = page.url();
  const individualFormUrl = new URL(individualUrl);
  individualFormUrl.searchParams.set('for_mid_term', '0');
  await showPage(tutorial, page, {
    eyebrow: 'Individual Entry',
    title: 'Select the Result Context',
    description:
      'The form keeps Academic Session and Term with the result. The page also identifies the selected Subject and Class before the student scores are entered.',
    url: individualFormUrl.toString(),
    target: page.getByText('Recording Full Term Result', { exact: true }),
    focus: [
      formSelect(page, /^Academic Session/),
      formSelect(page, /^Term/),
      page.getByText('Result Workflow Mathematics', { exact: true }),
      page.getByText('JSS 2 Result Recording', { exact: true }),
    ],
  });

  await chooseFullTerm(page);
  const studentControl = formSelect(page, /^Student/);
  await tutorial.step({
    eyebrow: 'Individual Entry',
    title: 'Choose the Student',
    description:
      'Search the Student field and choose the learner whose result you are recording. EduManager reloads the form with that student’s result context.',
    target: studentControl,
    action: async () => {
      await chooseAsyncOption(page, studentControl, 'Amina', /Amina/);
      await pauseMedium();
    },
  });

  const firstAssessment = inputByLabel(page, 'First Assessment');
  const secondAssessment = inputByLabel(page, 'Second Assessment');
  const examInput = inputByLabel(page, 'Exam');
  const individualSave = page
    .locator('form')
    .getByRole('button', { name: 'Submit', exact: true });
  await tutorial.focusOn([
    firstAssessment,
    secondAssessment,
    examInput,
    individualSave,
  ]);
  await tutorial.step({
    eyebrow: 'Individual Entry',
    title: 'Enter Assessment Scores',
    description:
      'Each assessment has its own maximum. These two assessment components contribute to the course result before the exam score is added.',
    target: firstAssessment,
    action: async () => {
      await typeForTutorial(page, firstAssessment, '16');
      await typeForTutorial(page, secondAssessment, '17');
    },
  });
  await tutorial.step({
    eyebrow: 'Individual Entry',
    title: 'Enter the Exam Score',
    description:
      'The Exam field records the end-of-term exam component. The final Result is the combined score, and EduManager derives the grade from it.',
    target: examInput,
    action: async () => typeForTutorial(page, examInput, '48'),
  });
  await tutorial.step({
    eyebrow: 'Individual Entry',
    title: 'Save the Student Result',
    description:
      'Save the score row when the assessment and exam values are ready. This page has no separate comment field; comments are handled later in the term-result and evaluation workflows.',
    target: individualSave,
    action: async () => {
      await clickWithCursor(page, individualSave);
      await pauseMedium();
    },
  });
  await tutorial.clearFocus();
  await tutorial.step({
    eyebrow: 'Individual Entry',
    title: 'Review the Saved Row',
    description:
      'The Student Results table now shows the learner, assessment values, exam, combined Result, and Grade. This is the first review point after saving.',
    target: page.getByText('Student Results', { exact: true }),
    holdBeforeActionMs: pace.long,
  });

  await page.goto(`${institutionBaseUrl}/course-teachers/index`, {
    waitUntil: 'domcontentloaded',
  });
  await waitForDestination(page);
  const classRow = rowWithText(
    page,
    /Result Workflow Mathematics.*JSS 2 Result Recording/
  );
  await tutorial.step({
    eyebrow: 'Class Entry',
    title: 'Return to the Class Subject Assignment',
    description:
      'Use the same subject-and-class assignment when you want to record several learners together.',
    target: classRow,
    action: async () => openRecordMenu(page, classRow, 'All Class Students'),
  });
  await page.waitForURL(/\/record-class-results\//, { timeout: 20000 });
  await waitForDestination(page);
  await tutorial.announce(
    'Class Entry',
    'Record the Whole Class',
    'The class page keeps all learners for the selected subject together, so scores can be entered and reviewed in one pass.',
    pace.long
  );
  await chooseFullTerm(page);

  const classCards = page.locator('input[type="number"]');
  const classInputs = [classCards.nth(3), classCards.nth(4), classCards.nth(5)];
  const classSave = page.getByRole('button', { name: 'Submit', exact: true });
  await tutorial.focusOn([...classInputs, classSave], { maxScale: 1.25 });
  await tutorial.step({
    eyebrow: 'Class Entry',
    title: 'Review Learners and Score Columns',
    description:
      'Each learner has the same assessment columns and Exam field. Amina’s saved values are already visible, while David’s row is ready for entry.',
    target: page.getByText('David Result', { exact: true }),
    holdBeforeActionMs: pace.long,
  });
  await tutorial.step({
    eyebrow: 'Class Entry',
    title: 'Enter the Remaining Class Scores',
    description:
      'Enter the assessment components and exam score for the next learner. The card displays a running Total as the values are entered.',
    target: classInputs[0],
    action: async () => {
      await typeForTutorial(page, classInputs[0], '15');
      await typeForTutorial(page, classInputs[1], '18');
      await typeForTutorial(page, classInputs[2], '50');
    },
  });
  await tutorial.step({
    eyebrow: 'Class Entry',
    title: 'Save the Class Result Set',
    description:
      'Submit the class sheet after checking the learner rows. EduManager records changed scores, calculates class positions, and processes the class summary.',
    target: classSave,
    action: async () => {
      await clickWithCursor(page, classSave);
      await pauseMedium();
    },
  });

  await showPage(tutorial, page, {
    eyebrow: 'Excel Workflow',
    title: 'Open Recorded Result Details',
    description:
      'The recorded-detail page summarizes each course-and-class result and provides the Upload Results and Download actions.',
    url: `${institutionBaseUrl}/course-result-info/index`,
    target: page.getByText('Recorded Result Detail', { exact: true }),
  });

  const uploadResultsButton = page.getByRole('button', {
    name: 'Upload Results',
    exact: true,
  });
  await tutorial.step({
    eyebrow: 'Excel Workflow',
    title: 'Locate the Course Upload Option',
    description:
      'Upload Results opens a course-level Excel import form. The Download button beside it opens the matching result-sheet export options.',
    target: uploadResultsButton,
    action: async () => clickWithCursor(page, uploadResultsButton),
  });
  const uploadModal = page.getByRole('dialog');
  await tutorial.focusOn([uploadModal], { maxScale: 1.25 });
  await tutorial.step({
    eyebrow: 'Excel Workflow',
    title: 'Read the Upload Context',
    description:
      'The upload form identifies the teacher, Academic Session, Term, and result type before accepting an Excel file. The sheet maps student rows to assessment and exam columns.',
    target: uploadModal,
    holdBeforeActionMs: pace.long,
  });
  const closeUploadModal = uploadModal
    .getByRole('button', {
      name: 'Close',
      exact: true,
    })
    .last();
  await tutorial.step({
    eyebrow: 'Excel Workflow',
    title: 'Close the Course Upload Preview',
    description:
      'Close this preview, then use the class-sheet importer to upload the seeded demonstration workbook.',
    target: closeUploadModal,
    action: async () => clickWithCursor(page, closeUploadModal),
  });
  await tutorial.clearFocus();

  await showPage(tutorial, page, {
    eyebrow: 'Excel Workflow',
    title: 'Open the Class Sheet Importer',
    description:
      'Upload Class Sheet is the whole-class Excel entry point. It asks for the Academic Session, term, class, and workbook.',
    url: `${institutionBaseUrl}/course-results/class-sheet/upload`,
    target: page.getByText('Upload Class Results', { exact: true }),
    focus: [page.locator('form').first()],
  });
  const uploadTerm = formSelect(page, /^term/);
  const uploadClass = formSelect(page, /^Class/);
  await tutorial.step({
    eyebrow: 'Excel Workflow',
    title: 'Select the Sheet Context',
    description:
      'Choose the session, term, and class that match the workbook. The importer reads the student ID column and matches subject columns to the subject names or codes used by the school.',
    target: uploadClass,
    action: async () => {
      await chooseReactSelectOption(page, uploadTerm, 'First');
      await chooseReactSelectOption(page, uploadClass, 'SS 1 Excel Import');
    },
  });
  const fileDropArea = page.getByText(/Drop a file or click here/).first();
  const fileInput = page.locator('input[type="file"]').first();
  await tutorial.step({
    eyebrow: 'Excel Workflow',
    title: 'Upload the Seeded Demo Sheet',
    description:
      'This workbook contains the student ID, student name, and the RWMATH subject column. The score is imported into the selected class result.',
    target: fileDropArea,
    action: async () => {
      await fileInput.setInputFiles(tutorialClassSheet);
      await pauseMedium();
    },
  });
  const uploadClassSubmit = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await tutorial.step({
    eyebrow: 'Excel Workflow',
    title: 'Submit the Class Workbook',
    description:
      'Confirm the selected class and submit the workbook. EduManager imports the course scores and updates the class result summary.',
    target: uploadClassSubmit,
    action: async () => {
      page.once('dialog', (dialog) => dialog.accept());
      await clickWithCursor(page, uploadClassSubmit);
      await page.waitForURL(/\/class-result-info\/index/, {
        timeout: 20000,
      });
      await waitForDestination(page);
    },
  });

  await showPage(tutorial, page, {
    eyebrow: 'Review Results',
    title: 'Review Course Results',
    description:
      'Student Results shows the individual records with assessment values, Exam, combined Result, Position, and Grade. Use filters for class, subject, session, student, teacher, and term.',
    url: `${institutionBaseUrl}/course-results/index`,
    target: page.getByText('Student Results', { exact: true }),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Review Results',
    title: 'Review Recorded Result Summaries',
    description:
      'Recorded Result Detail groups course results by subject and class and shows student counts, totals, maximums, minimums, and averages.',
    url: `${institutionBaseUrl}/course-result-info/index`,
    target: page.getByText('Recorded Result Detail', { exact: true }),
  });
  await showPage(tutorial, page, {
    eyebrow: 'Review Results',
    title: 'Review Class Analysis and Processing',
    description:
      'Class Result Analysis shows the class aggregate, number of learners and courses, totals, averages, positions, and the current lock state. Calculation locks the processed result until an authorized user unlocks it.',
    url: `${institutionBaseUrl}/class-result-info/index`,
    target: page.getByText('Class Result Analysis', { exact: true }),
  });

  const classResultRow = page
    .getByRole('row', { name: /JSS 2 Result Recording/ })
    .first();
  const studentResultsLink = classResultRow.getByRole('link', {
    name: 'Student Results',
    exact: true,
  });
  if (await studentResultsLink.count()) {
    await tutorial.step({
      eyebrow: 'Review Results',
      title: 'Open Term Results for the Class',
      description:
        'Student Results on the class summary opens the term-result list, where grades, positions, averages, and result details can be reviewed.',
      target: studentResultsLink,
      action: async () => clickWithCursor(page, studentResultsLink),
    });
    await waitForDestination(page);
    await tutorial.step({
      eyebrow: 'Review Results',
      title: 'Review Grades, Positions, and Comments',
      description:
        'The term-result view brings together each learner’s total, average, position, grade, and available teacher or principal comments for the result sheet.',
      target: page.locator('table').first(),
      holdBeforeActionMs: pace.long,
    });
  }

  await showPage(tutorial, page, {
    eyebrow: 'Result Sheets',
    title: 'Open the Result Sheet Review Area',
    description:
      'Result sheets present processed term results in a report-style layout for review and printing. Publishing is a separate administrative step and is not performed in this tutorial.',
    url: `${institutionBaseUrl}/result-sheets/dummy`,
    target: page.locator('table').first(),
    holdMs: pace.long,
  });

  await showPage(tutorial, page, {
    eyebrow: 'CBT Results',
    title: 'Open CBT Events',
    description:
      'CBT Events lists the school’s computer-based examinations. A completed exam outcome can later be transferred into an academic subject result.',
    url: `${institutionBaseUrl}/events`,
    target: page.getByText('List Events', { exact: true }),
  });
  const cbtEventRow = rowWithText(page, /Result Workflow CBT Exam/);
  const cbtViewLink = cbtEventRow.getByRole('link', {
    name: 'View',
    exact: true,
  });
  const cbtExamsLink = cbtEventRow.getByRole('link', {
    name: 'Exams',
    exact: true,
  });
  const cbtExamsHref = await cbtExamsLink.getAttribute('href');
  if (!cbtExamsHref) {
    throw new Error('The seeded CBT event did not expose an Exams link.');
  }
  await tutorial.step({
    eyebrow: 'CBT Results',
    title: 'Review the Completed CBT Event',
    description:
      'The event row shows the exam setup and provides View and Exams paths. Open View first to confirm the subject attached to the event.',
    target: cbtViewLink,
    action: async () => clickWithCursor(page, cbtViewLink),
  });
  await waitForDestination(page);
  await tutorial.step({
    eyebrow: 'CBT Results',
    title: 'See the Event Subject',
    description:
      'The event detail identifies the duration, class, and subject connected to the CBT outcome.',
    target: page.getByText('Event Details', { exact: true }),
    holdBeforeActionMs: pace.long,
  });
  await page.goto(new URL(cbtExamsHref, page.url()).toString(), {
    waitUntil: 'domcontentloaded',
  });
  await waitForDestination(page);
  await tutorial.step({
    eyebrow: 'CBT Results',
    title: 'Review the Student Outcome',
    description:
      'The exam list shows the completed student outcome, including the exam number, score, class, and Ended status.',
    target: page.getByText('List Exams', { exact: true }),
    holdBeforeActionMs: pace.long,
  });
  const examDetailLink = page.locator('a[title="Detail"]').first();
  if (await examDetailLink.count()) {
    await tutorial.step({
      eyebrow: 'CBT Results',
      title: 'Open the Exam Subject Detail',
      description:
        'The detail page shows the completed subject result before it is transferred into the main academic result record.',
      target: examDetailLink,
      action: async () => clickWithCursor(page, examDetailLink),
    });
    await waitForDestination(page);
    await tutorial.step({
      eyebrow: 'CBT Results',
      title: 'Confirm the Completed Score',
      description:
        'This is the score EduManager can carry into the academic result as the Exam component.',
      target: page.locator('main').first(),
      holdBeforeActionMs: pace.long,
    });
  }

  await showPage(tutorial, page, {
    eyebrow: 'CBT Transfer',
    title: 'Return to the CBT Transfer Action',
    description:
      'Back on the event list, the Upload action is the entry point for transferring the completed CBT outcome.',
    url: `${institutionBaseUrl}/events`,
    target: page.getByText('List Events', { exact: true }),
  });
  const cbtUploadButton = cbtEventRow.getByRole('button', { name: 'Upload' });
  await tutorial.step({
    eyebrow: 'CBT Transfer',
    title: 'Start the Result Transfer',
    description:
      'Open Upload on the completed event to map the CBT subject to its academic subject teacher.',
    target: cbtUploadButton,
    action: async () => clickWithCursor(page, cbtUploadButton),
  });
  const transferModal = page.getByRole('dialog');
  await tutorial.focusOn([transferModal], { maxScale: 1.25 });
  const transferTeacher = formSelect(page, /^Teacher/);
  await tutorial.step({
    eyebrow: 'CBT Transfer',
    title: 'Choose the Academic Subject Assignment',
    description:
      'Select the teacher assignment that represents the same subject and class in the academic result system.',
    target: transferTeacher,
    action: async () =>
      chooseAsyncOption(
        page,
        transferTeacher,
        'Result',
        /Result Workflow Teacher.*Result Workflow Mathematics.*JSS 3 CBT Transfer/
      ),
  });
  const transferExamCheckbox = transferModal.getByText(
    'Transfer to Exam scores',
    { exact: true }
  );
  await tutorial.step({
    eyebrow: 'CBT Transfer',
    title: 'Transfer the CBT Score to Exam',
    description:
      'Choose Transfer to Exam scores so the completed CBT mark becomes the Exam component of the academic course result.',
    target: transferExamCheckbox,
    action: async () => clickLabelText(page, 'Transfer to Exam scores'),
  });
  const transferSubmit = transferModal.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await tutorial.step({
    eyebrow: 'CBT Transfer',
    title: 'Submit the Transfer',
    description:
      'Submit the mapping after checking the Academic Session, Term, teacher assignment, and transfer destination.',
    target: transferSubmit,
    action: async () => {
      await clickWithCursor(page, transferSubmit);
      await pauseMedium();
    },
  });
  await tutorial.clearFocus();
  await tutorial.step({
    eyebrow: 'CBT Transfer',
    title: 'Confirm the Event Was Transferred',
    description:
      'The event list now marks the transfer and keeps the CBT outcome connected to the academic result workflow.',
    target: cbtEventRow,
    holdBeforeActionMs: pace.long,
  });

  await showPage(tutorial, page, {
    eyebrow: 'After Transfer',
    title: 'Find the Transferred Score in Results',
    description:
      'Return to Student Results to find the CBT learner’s academic row. The transferred value appears in the Exam column and contributes to Result and Grade.',
    url: `${institutionBaseUrl}/course-results/index`,
    target: page.locator('table').first(),
    focus: [page.locator('table').first()],
  });
  await showPage(tutorial, page, {
    eyebrow: 'After Transfer',
    title: 'Review Processing and Locking',
    description:
      'Class Result Analysis is where administrators review aggregates and the lock state after processing. Review the state before any later correction or publication step.',
    url: `${institutionBaseUrl}/class-result-info/index`,
    target: page.getByText('Class Result Analysis', { exact: true }),
  });

  await tutorial.announce(
    'Conclusion',
    'Four Ways to Record Results',
    'Use Single Student for focused entry, All Class Students for a class sheet, Excel upload for a prepared workbook, and CBT transfer when examination scores are ready. After recording, review course results, class analysis, term results, grades, positions, averages, and result sheets before any separate publishing decision.',
    pace.long
  );
  await tutorial.dismiss();
}
