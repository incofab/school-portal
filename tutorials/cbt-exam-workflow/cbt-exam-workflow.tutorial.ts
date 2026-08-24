import type { Locator, Page } from '@playwright/test';
import { cbtTutorialFixtures as fixtures, config, pace } from '../config';
import {
  chooseReactSelectOption,
  clickLabelText,
  clickWithCursor,
  hrefForLinkText,
  pauseLong,
  pauseMedium,
  pauseShort,
  reactSelectControl,
  typeForTutorial,
} from '../helpers/tutorial-actions';
import { Tutorial } from '../helpers/tutorial';

/**
 * "CBT Exam Workflow" walkthrough. Demonstrates the full, connected process
 * this app actually supports for computer-based testing:
 *
 *   admin builds a question bank (a section with objective and theory
 *   questions) for one subject -> admin creates a CBT event, attaches that
 *   section, and targets it at a class -> a student logs in through the
 *   dedicated CBT exam-login flow (event code + student code) and sits the
 *   exam, answering both question types -> the exam is submitted and
 *   scored -> the school reviews the submission, including the objective
 *   corrections and marking the theory answers.
 *
 * Every field, button, and route used here was verified against the real
 * CCD (question bank) controllers/Blade views and the Exams
 * controllers/pages — nothing here is invented. Two things worth knowing
 * up front:
 *
 * - The question bank (Subjects -> Question Bank -> Sessions/Questions) is
 *   a legacy server-rendered (Blade) area, not the React/Inertia app the
 *   rest of this app's tutorials drive — see tinymceContainer()/fillTinymce()
 *   below for how its rich-text question/option fields are handled.
 * - Reviewing a submitted paper's correct/incorrect answers
 *   (`exam-courseables.show`, the "Question Details" page) is a staff-only
 *   page in this app (`ExamCourseableController` only allows Admin/Teacher,
 *   with no exception) — there is no student-facing corrections page to
 *   demonstrate, so this tutorial doesn't claim there is one. The event's
 *   "Allow students to view corrections" setting instead controls whether
 *   *staff* see the correct answer highlighted when reviewing a paper.
 */
export async function runCbtExamWorkflowTutorial(page: Page): Promise<void> {
  const tutorial = await Tutorial.create(page);

  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  const emailInput = page.getByLabel('Email address');
  const passwordInput = page.getByLabel('Password', { exact: true });
  const loginButton = page.getByRole('button', { name: 'Login', exact: true });
  await emailInput.waitFor({ state: 'visible' });
  await pauseMedium();

  await tutorial.announce(
    'Tutorial',
    'The CBT Exam Workflow',
    'See how a school builds a question bank, creates a CBT exam, how a student sits it, and how the school reviews what was submitted.',
    pace.long
  );

  await tutorial.focusOn([emailInput, passwordInput, loginButton]);
  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In as the Admin',
    description:
      'Log in with an administrator account to build the question bank and set up the exam.',
    target: emailInput,
    action: async () => {
      await typeForTutorial(page, emailInput, config.demoEmail);
      await typeForTutorial(page, passwordInput, config.demoPassword);
      await clickWithCursor(page, loginButton);
    },
  });

  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  const adminAccountMenuButton = page.getByRole('button', {
    name: 'Open menu',
  });
  await adminAccountMenuButton.waitFor({ state: 'visible', timeout: 20000 });
  // The question bank lives in a separate, legacy server-rendered area
  // (see the file docblock) with its own minimal sidebar — this base URL
  // lets us jump straight back into the main React app afterwards rather
  // than looking for React-app sidebar links that don't exist there.
  const instBaseUrl = page.url().replace(/\/dashboard(\/|$|\?).*$/, '');
  await pauseMedium();

  // ===========================================================
  // Part 1 — Build the question bank
  // ===========================================================
  await tutorial.announce(
    'Part 1 of 4',
    'Build the Question Bank',
    `We'll add a section of questions to the "${fixtures.courseTitle}" subject — one objective question set and one theory question set.`,
    pace.long
  );

  const subjectsHref = await hrefForLinkText(page, 'All Subject');
  await page.goto(subjectsHref, { waitUntil: 'networkidle' });

  const questionBankLink = page.getByRole('link', { name: 'Question Bank' });
  await questionBankLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([questionBankLink], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'Subjects',
    title: 'Open the Question Bank',
    description: `Click Question Bank on the "${fixtures.courseTitle}" row.`,
    target: questionBankLink,
    action: async () => clickWithCursor(page, questionBankLink),
  });
  await tutorial.clearFocus();

  // ---- Create a section --------------------------------------
  // Blade's "New" links wrap the icon + text across lines, so the
  // computed accessible name carries surrounding whitespace — match
  // loosely rather than with `exact: true` (which the React/Chakra "New"
  // links elsewhere in this tutorial don't need).
  const newSessionLink = page.getByRole('link', { name: 'New' });
  await newSessionLink.waitFor({ state: 'visible' });

  await tutorial.announce(
    'Question Bank',
    'Create a Section',
    'Questions are organized into sections within a subject — create one to hold this exam’s questions.',
    pace.medium
  );

  await tutorial.focusOn([newSessionLink], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'Sections',
    title: 'Start a New Section',
    description: 'Click New to create a section.',
    target: newSessionLink,
    action: async () => clickWithCursor(page, newSessionLink),
  });
  await tutorial.clearFocus();

  const sessionInput = page.locator('input[name="session"]');
  const generalInstructionsInput = page.locator(
    'textarea[name="general_instructions"]'
  );
  const sessionSubmitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await sessionInput.waitFor({ state: 'visible' });

  await tutorial.focusOn(
    [sessionInput, generalInstructionsInput, sessionSubmitButton],
    { maxScale: 1.3 }
  );
  await tutorial.step({
    eyebrow: 'New Section',
    title: 'Name the Section',
    description: 'Give this section a name so it’s easy to recognize later.',
    target: sessionInput,
    action: async () =>
      typeForTutorial(page, sessionInput, fixtures.sessionName),
  });
  await tutorial.step({
    eyebrow: 'New Section',
    title: 'Add General Instructions',
    description:
      'Optional instructions shown to students at the start of this section.',
    target: generalInstructionsInput,
    action: async () =>
      typeForTutorial(
        page,
        generalInstructionsInput,
        'Answer all questions in this section.'
      ),
  });
  await tutorial.step({
    eyebrow: 'New Section',
    title: 'Save the Section',
    description: 'Click Submit to create it.',
    target: sessionSubmitButton,
    action: async () => clickWithCursor(page, sessionSubmitButton),
  });
  await tutorial.clearFocus();

  // ---- Create objective (OBJ) questions -----------------------
  const questionsLink = page.getByRole('link', { name: 'Questions' }).first();
  await questionsLink.waitFor({ state: 'visible' });
  const questionsHref = await hrefForLinkText(page, 'Questions');
  const theoryQuestionsHref = await hrefForLinkText(page, 'Theory Questions');

  await tutorial.announce(
    'Question Bank',
    'Add Objective Questions',
    'Objective questions give students a fixed set of options to choose from.',
    pace.medium
  );

  await page.goto(questionsHref, { waitUntil: 'networkidle' });
  const newQuestionLink = page.getByRole('link', { name: 'New' });
  await newQuestionLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([newQuestionLink], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'Objective Questions',
    title: 'Start a New Question',
    description: 'Click New to add the first objective question.',
    target: newQuestionLink,
    action: async () => clickWithCursor(page, newQuestionLink),
  });
  await tutorial.clearFocus();

  await createObjectiveQuestion(tutorial, page, {
    order: '1 of 2',
    question: 'What is the capital of Nigeria?',
    options: { a: 'Lagos', b: 'Abuja', c: 'Kano', d: 'Enugu' },
    answer: 'B',
  });

  await createObjectiveQuestion(tutorial, page, {
    order: '2 of 2',
    question: 'Which planet is known as the Red Planet?',
    options: { a: 'Earth', b: 'Mars', c: 'Venus', d: 'Jupiter' },
    answer: 'B',
  });

  // ---- Create theory questions ---------------------------------
  await tutorial.announce(
    'Question Bank',
    'Add Theory Questions',
    'Theory questions let a student type a free-text answer, marked later by a teacher.',
    pace.medium
  );

  await page.goto(theoryQuestionsHref, { waitUntil: 'networkidle' });
  const newTheoryQuestionLink = page.getByRole('link', { name: 'New' });
  await newTheoryQuestionLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([newTheoryQuestionLink], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'Theory Questions',
    title: 'Start a New Theory Question',
    description: 'Click New to add the first theory question.',
    target: newTheoryQuestionLink,
    action: async () => clickWithCursor(page, newTheoryQuestionLink),
  });
  await tutorial.clearFocus();

  await createTheoryQuestion(tutorial, page, {
    order: '1 of 2',
    question: 'Explain briefly what CBT stands for in the context of exams.',
    marks: '5',
    answer: 'Computer Based Test',
  });

  await createTheoryQuestion(tutorial, page, {
    order: '2 of 2',
    question:
      'State one advantage of taking an exam on a computer rather than on paper.',
    marks: '3',
    answer: 'Results can be produced much faster than manual marking.',
  });

  // ===========================================================
  // Part 2 — Create the CBT event
  // ===========================================================
  // The question bank area's own sidebar has no "CBT Events" link — head
  // back to the main dashboard first.
  await page.goto(`${instBaseUrl}/dashboard`, { waitUntil: 'networkidle' });
  await adminAccountMenuButton.waitFor({ state: 'visible', timeout: 20000 });

  await tutorial.announce(
    'Part 2 of 4',
    'Create the CBT Exam',
    'With the questions ready, create the exam event that brings them together for students.',
    pace.long
  );

  const eventsHref = await hrefForLinkText(page, 'CBT Events');
  await page.goto(eventsHref, { waitUntil: 'networkidle' });
  const newEventLink = page.getByRole('link', { name: 'New', exact: true });
  await newEventLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([newEventLink], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'CBT Events',
    title: 'Start a New Event',
    description: 'Click New to create a CBT exam event.',
    target: newEventLink,
    action: async () => clickWithCursor(page, newEventLink),
  });
  await tutorial.clearFocus();

  const eventTitleInput = page.getByLabel('Event title');
  const eventDurationInput = page.getByLabel('Duration [mins]');
  const eventStartsAtInput = page.getByLabel('Start time');
  const eventTypeControl = reactSelectControl(page, 'Event Type');
  const classGroupControl = reactSelectControl(page, 'Class Group');
  await eventTitleInput.waitFor({ state: 'visible' });

  await tutorial.focusOn(
    [
      eventTitleInput,
      eventDurationInput,
      eventStartsAtInput,
      eventTypeControl,
      classGroupControl,
    ],
    { maxScale: 1.3 }
  );

  await tutorial.step({
    eyebrow: 'New Event',
    title: 'Name the Exam',
    description: 'Give the exam a clear title.',
    target: eventTitleInput,
    action: async () =>
      typeForTutorial(page, eventTitleInput, fixtures.eventTitle),
  });

  await tutorial.step({
    eyebrow: 'New Event',
    title: 'Set the Duration',
    description: 'Students get 30 minutes once they start the exam.',
    target: eventDurationInput,
    action: async () => typeForTutorial(page, eventDurationInput, '30'),
  });

  await tutorial.step({
    eyebrow: 'New Event',
    title: 'Set the Start Time',
    description:
      'We’ll set this to right now, so the exam is immediately available.',
    target: eventStartsAtInput,
    action: async () => {
      await clickWithCursor(page, eventStartsAtInput);
      await eventStartsAtInput.fill(currentDateTimeLocalValue());
      await pauseShort();
    },
  });

  await tutorial.step({
    eyebrow: 'New Event',
    title: 'Event Type',
    description:
      'This is a Student Test — an exam sat by students already enrolled at the school.',
    target: eventTypeControl,
  });

  await tutorial.step({
    eyebrow: 'New Event',
    title: 'Target a Class',
    description: `Choose "${fixtures.classGroupName}" so students in that group can access this exam.`,
    target: classGroupControl,
    action: async () =>
      chooseReactSelectOption(page, classGroupControl, fixtures.classGroupName),
  });

  // The "Add Subject" section and the corrections checkbox sit further
  // down the same card — a fresh focus for that part of the form.
  //
  // "Add Subject" itself isn't a safe anchor here: the sidebar has its own,
  // unrelated "Add Subject" nav item (for creating a new subject/course)
  // that also matches `getByText(..., {exact:true})` and sits earlier in
  // the DOM, so `following::` from it would sweep up every field above
  // this section instead of just the two selects inside it. Anchor from
  // "Class Group" instead (unique on this page, and already needed above)
  // — its own react-select control is the 1st following match, so the
  // course/section selects are the 2nd and 3rd.
  const classGroupLabel = page.getByText('Class Group', { exact: true });
  const addSubjectHeading = page
    .getByText('Add Subject', { exact: true })
    .last();
  const addSubjectCourseControl = classGroupLabel.locator(
    'xpath=following::div[contains(@class,"control") and not(contains(@class,"chakra-form-control"))][2]'
  );
  const correctionsCheckboxText = page.getByText(
    'Allow students to view corrections after the exam',
    { exact: true }
  );
  const eventSubmitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await tutorial.focusOn(
    [addSubjectHeading, addSubjectCourseControl, correctionsCheckboxText],
    { maxScale: 1.3 }
  );

  await tutorial.announce(
    'Attach the Questions',
    'Add the Subject and Section',
    'Attach the subject and the section of questions created earlier.',
    pace.medium
  );

  await tutorial.step({
    eyebrow: 'Attach the Questions',
    title: 'Choose the Subject',
    description: `Select "${fixtures.courseTitle}".`,
    target: addSubjectCourseControl,
    action: async () =>
      chooseReactSelectOption(
        page,
        addSubjectCourseControl,
        fixtures.courseTitle
      ),
  });

  const addSubjectSessionControl = classGroupLabel.locator(
    'xpath=following::div[contains(@class,"control") and not(contains(@class,"chakra-form-control"))][3]'
  );
  await addSubjectSessionControl.waitFor({ state: 'visible' });
  await tutorial.focusOn(
    [addSubjectHeading, addSubjectSessionControl, correctionsCheckboxText],
    { maxScale: 1.3 }
  );
  await tutorial.step({
    eyebrow: 'Attach the Questions',
    title: 'Choose the Section',
    description: `Select "${fixtures.sessionName}" — it’s added to the exam automatically.`,
    target: addSubjectSessionControl,
    action: async () =>
      chooseReactSelectOption(
        page,
        addSubjectSessionControl,
        fixtures.sessionName
      ),
  });
  await pauseShort();

  await tutorial.focusOn([correctionsCheckboxText, eventSubmitButton], {
    maxScale: 1.3,
  });
  await tutorial.step({
    eyebrow: 'Corrections',
    title: 'Allow Corrections to Be Reviewed',
    description:
      'When this is checked, staff reviewing a submitted paper later will see the correct answer highlighted.',
    target: correctionsCheckboxText,
    action: async () =>
      clickLabelText(page, 'Allow students to view corrections after the exam'),
  });

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Exam',
    description: 'Click Submit to create the CBT exam event.',
    target: eventSubmitButton,
    action: async () => clickWithCursor(page, eventSubmitButton),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/events'), { timeout: 20000 })
    .catch(() => undefined);
  await pauseMedium();

  const eventRow = page.locator('tr', { hasText: fixtures.eventTitle });
  await eventRow.waitFor({ state: 'visible', timeout: 20000 });
  const eventCode = (await eventRow.locator('td').nth(1).textContent())?.trim();
  if (!eventCode) {
    throw new Error(
      'Could not read the generated event code from the events list.'
    );
  }

  await tutorial.focusOn([eventRow], { maxScale: 1.4 });
  await tutorial.announce(
    'Exam Created',
    'Every Exam Gets a Code',
    `This exam's code is ${eventCode} — students use it, together with their own student code, to access it.`,
    pace.long
  );
  await tutorial.clearFocus();

  // ===========================================================
  // Switch roles: admin -> student
  // ===========================================================
  await tutorial.announce(
    'Switching Roles',
    'Now, as the Student',
    "Let's log out and access the exam the way a student actually would.",
    pace.long
  );

  await logout(tutorial, page, adminAccountMenuButton);

  // ===========================================================
  // Part 3 — Student sits the exam
  // ===========================================================
  await tutorial.announce(
    'Part 3 of 4',
    'Access the Exam',
    'CBT exams have their own login, separate from the regular sign-in — no account or password needed.',
    pace.long
  );

  const examLoginMenuButton = page.getByRole('button', { name: 'Exam Login' });
  await examLoginMenuButton.waitFor({ state: 'visible' });
  await tutorial.focusOn([examLoginMenuButton], { maxScale: 1.4 });
  await tutorial.step({
    eyebrow: 'Exam Login',
    title: 'Open the Exam Login Menu',
    description: 'Click Exam Login on the main login page.',
    target: examLoginMenuButton,
    action: async () => clickWithCursor(page, examLoginMenuButton),
  });

  const studentTestMenuItem = page.getByRole('menuitem', {
    name: 'Student Test',
  });
  await studentTestMenuItem.waitFor({ state: 'visible' });
  await tutorial.focusOn([examLoginMenuButton, studentTestMenuItem], {
    maxScale: 1.4,
  });
  await tutorial.step({
    eyebrow: 'Exam Login',
    title: 'Choose Student Test',
    description: 'This exam is a Student Test, so we’ll pick that option.',
    target: studentTestMenuItem,
    action: async () => clickWithCursor(page, studentTestMenuItem),
  });
  await tutorial.clearFocus();

  await page.waitForURL(/\/student\/exam-login(\/|$|\?)/, { timeout: 20000 });
  const studentCodeInput = page.getByLabel('Student Code');
  const eventCodeInput = page.getByLabel('Event Code');
  const startExamButton = page.getByRole('button', {
    name: 'Start Exam',
    exact: true,
  });
  await studentCodeInput.waitFor({ state: 'visible' });

  await tutorial.focusOn([studentCodeInput, eventCodeInput, startExamButton]);
  await tutorial.step({
    eyebrow: 'Exam Access',
    title: 'Enter the Student Code',
    description:
      'Every student has their own code, given to them by the school.',
    target: studentCodeInput,
    action: async () =>
      typeForTutorial(page, studentCodeInput, fixtures.studentCode),
  });
  await tutorial.step({
    eyebrow: 'Exam Access',
    title: 'Enter the Exam Code',
    description: `Enter the exam's code — ${eventCode} — shown by the school.`,
    target: eventCodeInput,
    action: async () => typeForTutorial(page, eventCodeInput, eventCode),
  });
  await tutorial.step({
    eyebrow: 'Exam Access',
    title: 'Start the Exam',
    description: 'Click Start Exam to begin — the timer starts immediately.',
    target: startExamButton,
    action: async () => clickWithCursor(page, startExamButton),
  });
  await tutorial.clearFocus();

  await page.waitForURL(/\/my-exam\/display\//, { timeout: 20000 });
  await page.locator('.question-container').waitFor({ state: 'visible' });
  await pauseMedium();

  await tutorial.announce(
    'The Exam Screen',
    'One Question at a Time',
    'Students see one question at a time, with a timer running and a question map to jump between questions.',
    pace.long
  );

  // ---- Objective question 1 (answered correctly) ----------------
  await focusOnCurrentQuestion(tutorial, page);
  await tutorial.step({
    eyebrow: 'Objective — Question 1 of 2',
    title: 'Choose the Correct Answer',
    description: `We'll intentionally select "Abuja" — the correct answer.`,
    target: page.getByText('Abuja', { exact: true }),
    action: async () => clickLabelText(page, 'Abuja'),
  });

  const nextQuestionButton = page.getByRole('button', {
    name: 'Next',
    exact: true,
  });
  await tutorial.step({
    eyebrow: 'Objective — Question 1 of 2',
    title: 'Move to the Next Question',
    description: 'Answers are saved automatically as the student moves on.',
    target: nextQuestionButton,
    action: async () => clickWithCursor(page, nextQuestionButton),
  });

  // ---- Objective question 2 (answered incorrectly, on purpose) --
  await focusOnCurrentQuestion(tutorial, page);
  await tutorial.step({
    eyebrow: 'Objective — Question 2 of 2',
    title: 'Choose a Wrong Answer, on Purpose',
    description:
      'This time we’ll intentionally select "Earth" — an incorrect answer — to show how a mistake is scored.',
    target: page.getByText('Earth', { exact: true }),
    action: async () => clickLabelText(page, 'Earth'),
  });

  // ---- Switch to theory questions --------------------------------
  const theoryTabButton = page.getByRole('button', { name: 'Theory (2)' });
  await tutorial.focusOn([theoryTabButton], { maxScale: 1.3 });
  await tutorial.step({
    eyebrow: 'Theory Questions',
    title: 'Switch to Theory Questions',
    description: 'Click Theory to answer this subject’s free-text questions.',
    target: theoryTabButton,
    action: async () => clickWithCursor(page, theoryTabButton),
  });

  // ---- Theory question 1 -----------------------------------------
  await focusOnCurrentQuestion(tutorial, page);
  const theoryAnswerInput = page.getByPlaceholder('Type your answer here');
  await tutorial.step({
    eyebrow: 'Theory — Question 1 of 2',
    title: 'Type the Answer',
    description:
      'Theory answers are typed in freely, then marked by a teacher afterwards.',
    target: theoryAnswerInput,
    action: async () =>
      typeForTutorial(page, theoryAnswerInput, 'Computer Based Test'),
  });
  await tutorial.step({
    eyebrow: 'Theory — Question 1 of 2',
    title: 'Move to the Next Question',
    description: 'Click Next to move to the last question.',
    target: nextQuestionButton,
    action: async () => clickWithCursor(page, nextQuestionButton),
  });

  // ---- Theory question 2 -----------------------------------------
  await focusOnCurrentQuestion(tutorial, page);
  await tutorial.step({
    eyebrow: 'Theory — Question 2 of 2',
    title: 'Type the Final Answer',
    description: 'One last free-text answer before submitting.',
    target: theoryAnswerInput,
    action: async () =>
      typeForTutorial(
        page,
        theoryAnswerInput,
        'It gives results faster than paper marking.'
      ),
  });

  // ---- Submit the exam ---------------------------------------------
  const submitExamButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await tutorial.focusOn([submitExamButton], { maxScale: 1.3 });
  await tutorial.step({
    eyebrow: 'Submitting',
    title: 'Submit the Exam',
    description:
      'Once every question is answered, click Submit to finish the exam.',
    target: submitExamButton,
    action: async () => {
      page.once('dialog', (dialog) => dialog.accept());
      await clickWithCursor(page, submitExamButton);
    },
  });
  await tutorial.clearFocus();

  await page.waitForURL(/\/exam-result\//, { timeout: 20000 });
  await pauseLong();

  await tutorial.announce(
    'Exam Submitted',
    'The Student Sees Their Score Right Away',
    'The objective score is available immediately — the theory score is added once a teacher marks it.',
    pace.long
  );

  // ===========================================================
  // Switch roles: student -> admin
  // ===========================================================
  await tutorial.dismiss();
  await tutorial.announce(
    'Switching Roles',
    'Now, Back as the School',
    "Let's sign back in as the admin to review this submission.",
    pace.long
  );

  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  await emailInput.waitFor({ state: 'visible' });
  await tutorial.focusOn([emailInput, passwordInput, loginButton]);
  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In as the Admin',
    description: 'Let’s review what the student submitted.',
    target: emailInput,
    action: async () => {
      await typeForTutorial(page, emailInput, config.demoEmail);
      await typeForTutorial(page, passwordInput, config.demoPassword);
      await clickWithCursor(page, loginButton);
    },
  });
  await tutorial.clearFocus();

  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  await adminAccountMenuButton.waitFor({ state: 'visible', timeout: 20000 });
  await pauseMedium();

  // ===========================================================
  // Part 4 — Review the submission
  // ===========================================================
  await tutorial.announce(
    'Part 4 of 4',
    'Review the Submission',
    'The school can see every student’s exam, check their objective answers, and mark their theory answers.',
    pace.long
  );

  const reviewEventsHref = await hrefForLinkText(page, 'CBT Events');
  await page.goto(reviewEventsHref, { waitUntil: 'networkidle' });
  const examsLink = page.getByRole('link', { name: 'Exams', exact: true });
  await examsLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([examsLink], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'CBT Events',
    title: 'View the Exams for This Event',
    description: 'Click Exams to see every student who has sat this exam.',
    target: examsLink,
    action: async () => clickWithCursor(page, examsLink),
  });
  await tutorial.clearFocus();

  const detailLink = page.getByRole('link', { name: 'Detail', exact: true });
  await detailLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([detailLink], { maxScale: 1.4 });
  await tutorial.announce(
    'Exams',
    'The Student’s Submission',
    'The objective score is already recorded here.',
    pace.medium
  );
  await tutorial.step({
    eyebrow: 'Exams',
    title: 'Open the Subject Breakdown',
    description: 'Click Detail to see the score for each subject.',
    target: detailLink,
    action: async () => clickWithCursor(page, detailLink),
  });
  await tutorial.clearFocus();

  const questionDetailsLink = page.getByRole('link', {
    name: 'Question Details',
    exact: true,
  });
  await questionDetailsLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([questionDetailsLink], { maxScale: 1.4 });
  await tutorial.step({
    eyebrow: 'Exam Subjects',
    title: 'Open the Question-by-Question Review',
    description: 'Click Question Details to review exactly what was answered.',
    target: questionDetailsLink,
    action: async () => clickWithCursor(page, questionDetailsLink),
  });
  await tutorial.clearFocus();

  const objectiveTabPanel = page.getByRole('tabpanel').first();
  await objectiveTabPanel.waitFor({ state: 'visible' });
  await pauseMedium();
  await tutorial.focusOn([objectiveTabPanel], { maxScale: 1.2 });
  await tutorial.announce(
    'Objective Review',
    'Correct Answers Are Highlighted',
    'Because "Allow students to view corrections" was checked on this exam, the correct option is highlighted in green for staff reviewing the paper.',
    pace.long
  );
  await tutorial.clearFocus();

  const theoryReviewTab = page.getByRole('tab', { name: 'Theory (2)' });
  await tutorial.focusOn([theoryReviewTab], { maxScale: 1.3 });
  await tutorial.step({
    eyebrow: 'Theory Review',
    title: 'Switch to Theory',
    description: 'Click Theory to mark the student’s free-text answers.',
    target: theoryReviewTab,
    action: async () => clickWithCursor(page, theoryReviewTab),
  });

  const firstScoreInput = page.getByLabel('Score out of 5');
  const secondScoreInput = page.getByLabel('Score out of 3');
  const evaluateButton = page.getByRole('button', {
    name: 'Mark Theory Evaluated',
    exact: true,
  });
  await firstScoreInput.waitFor({ state: 'visible' });

  await tutorial.focusOn([firstScoreInput, secondScoreInput, evaluateButton], {
    maxScale: 1.2,
  });
  await tutorial.step({
    eyebrow: 'Theory Review',
    title: 'Score the First Answer',
    description:
      'The student’s answer matches the expected answer — award full marks.',
    target: firstScoreInput,
    action: async () => {
      await clickWithCursor(page, firstScoreInput);
      await firstScoreInput.fill('5');
    },
  });
  await tutorial.step({
    eyebrow: 'Theory Review',
    title: 'Score the Second Answer',
    description: 'A valid advantage was given — award full marks here too.',
    target: secondScoreInput,
    action: async () => {
      await clickWithCursor(page, secondScoreInput);
      await secondScoreInput.fill('3');
    },
  });
  await tutorial.step({
    eyebrow: 'Theory Review',
    title: 'Save the Evaluation',
    description: 'Click Mark Theory Evaluated to record these scores.',
    target: evaluateButton,
    action: async () => clickWithCursor(page, evaluateButton),
  });
  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle').catch(() => undefined);
  await pauseLong();

  await tutorial.announce(
    'All Done',
    'Tutorial Complete',
    'You now know how a school builds a CBT question bank, creates and targets an exam, how a student sits it, and how the school reviews and marks the submission.',
    pace.long
  );
  await tutorial.dismiss();
}

/** Logs the current user out via the account menu and waits for the login page. */
async function logout(
  tutorial: Tutorial,
  page: Page,
  accountMenuButton: Locator
): Promise<void> {
  await tutorial.focusOn([accountMenuButton], { maxScale: 1.4 });
  await clickWithCursor(page, accountMenuButton);
  await pauseShort();

  const logoutItem = page.getByRole('menuitem', { name: 'Logout' });
  await logoutItem.waitFor({ state: 'visible' });
  await tutorial.focusOn([accountMenuButton, logoutItem], { maxScale: 1.4 });

  await clickWithCursor(page, logoutItem);
  await tutorial.clearFocus();

  await page.waitForURL(/\/login(\/|$|\?)/, { timeout: 20000 });
  await pauseMedium();
}

/**
 * Zooms in on the exam page's current question card
 * (`.question-container` in display-exam.tsx) so the whole question and its
 * options/textarea stay framed while a student answers it. Re-fit before
 * each question rather than mid-question — the content fully changes
 * between questions, unlike fields within one still-visible form.
 */
async function focusOnCurrentQuestion(
  tutorial: Tutorial,
  page: Page
): Promise<void> {
  const container = page.locator('.question-container');
  await container.waitFor({ state: 'visible' });
  await tutorial.focusOn([container], { maxScale: 1.3 });
}

/**
 * Locates the TinyMCE-rendered rich-text editor for a named `<textarea
 * class="useEditor">` in the CCD (question bank) Blade forms — TinyMCE
 * hides the original textarea and inserts its `.tox-tinymce` editor UI as
 * the next sibling, containing an iframe whose `<body>` is the actual
 * editable surface. Returns the outer `.tox-tinymce` container, suitable
 * for highlighting; use `fillTinymce()` to type into it.
 */
function tinymceContainer(page: Page, textareaName: string): Locator {
  return page.locator(
    `xpath=//textarea[@name="${textareaName}"]/following-sibling::*[contains(@class,"tox-tinymce")][1]`
  );
}

/** Types `text` into the named TinyMCE editor (see `tinymceContainer`). */
async function fillTinymce(
  page: Page,
  textareaName: string,
  text: string
): Promise<void> {
  const body = page
    .frameLocator(
      `xpath=//textarea[@name="${textareaName}"]/following-sibling::*[contains(@class,"tox-tinymce")][1]//iframe`
    )
    .locator('body');
  await body.click();
  await body.fill('');
  await body.pressSequentially(text, { delay: 15 });
  await pauseShort();
}

interface ObjectiveQuestionData {
  order: string;
  question: string;
  options: { a: string; b: string; c: string; d: string };
  answer: 'A' | 'B' | 'C' | 'D';
}

/**
 * Fills and submits one objective question on the CCD "Create Question"
 * Blade form. Question number is pre-filled and read-only, so it's left
 * alone; option E and the (optional) answer explanation are skipped to
 * keep the dummy data simple, matching the four options actually filled.
 */
async function createObjectiveQuestion(
  tutorial: Tutorial,
  page: Page,
  data: ObjectiveQuestionData
): Promise<void> {
  const questionField = tinymceContainer(page, 'question');
  const optionA = tinymceContainer(page, 'option_a');
  const optionB = tinymceContainer(page, 'option_b');
  const optionC = tinymceContainer(page, 'option_c');
  const optionD = tinymceContainer(page, 'option_d');
  const answerRadio = page.locator(`#answer_${data.answer.toLowerCase()}`);
  const submitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await questionField.waitFor({ state: 'visible' });
  await tutorial.focusOn(
    [questionField, optionA, optionB, optionC, optionD, submitButton],
    { maxScale: 1.3 }
  );

  await tutorial.step({
    eyebrow: `Objective Question ${data.order}`,
    title: 'Write the Question',
    description: 'Type the question text into the rich text editor.',
    target: questionField,
    action: async () => fillTinymce(page, 'question', data.question),
  });

  await tutorial.step({
    eyebrow: `Objective Question ${data.order}`,
    title: 'Add the Answer Options',
    description: 'Fill in options A to D for students to choose from.',
    target: optionA,
    action: async () => {
      await fillTinymce(page, 'option_a', data.options.a);
      await fillTinymce(page, 'option_b', data.options.b);
      await fillTinymce(page, 'option_c', data.options.c);
      await fillTinymce(page, 'option_d', data.options.d);
    },
  });

  await tutorial.step({
    eyebrow: `Objective Question ${data.order}`,
    title: 'Mark the Correct Answer',
    description: `Select ${data.answer} as the correct option.`,
    target: answerRadio,
    action: async () => clickWithCursor(page, answerRadio),
  });

  await tutorial.step({
    eyebrow: `Objective Question ${data.order}`,
    title: 'Save the Question',
    description: 'Click Submit to add this question to the bank.',
    target: submitButton,
    action: async () => clickWithCursor(page, submitButton),
  });
  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  await pauseShort();
}

interface TheoryQuestionData {
  order: string;
  question: string;
  marks: string;
  answer: string;
}

/** Fills and submits one theory question on the CCD "Create Theory Question" Blade form. */
async function createTheoryQuestion(
  tutorial: Tutorial,
  page: Page,
  data: TheoryQuestionData
): Promise<void> {
  const questionField = tinymceContainer(page, 'question');
  const marksInput = page.locator('input[name="marks"]');
  const answerField = tinymceContainer(page, 'answer');
  const submitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await questionField.waitFor({ state: 'visible' });
  await tutorial.focusOn(
    [questionField, marksInput, answerField, submitButton],
    { maxScale: 1.3 }
  );

  await tutorial.step({
    eyebrow: `Theory Question ${data.order}`,
    title: 'Write the Question',
    description: 'Theory questions let a student type a free-text answer.',
    target: questionField,
    action: async () => fillTinymce(page, 'question', data.question),
  });

  await tutorial.step({
    eyebrow: `Theory Question ${data.order}`,
    title: 'Set the Marks',
    description: `This question is worth ${data.marks} mark${
      data.marks === '1' ? '' : 's'
    }.`,
    target: marksInput,
    action: async () => {
      await clickWithCursor(page, marksInput);
      await marksInput.fill(data.marks);
    },
  });

  await tutorial.step({
    eyebrow: `Theory Question ${data.order}`,
    title: 'Enter the Expected Answer',
    description:
      'This is used by the school when scoring the theory answers later.',
    target: answerField,
    action: async () => fillTinymce(page, 'answer', data.answer),
  });

  await tutorial.step({
    eyebrow: `Theory Question ${data.order}`,
    title: 'Save the Question',
    description: 'Click Submit to add this question to the bank.',
    target: submitButton,
    action: async () => clickWithCursor(page, submitButton),
  });
  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  await pauseShort();
}

/** Current local date/time formatted for an `<input type="datetime-local">`. */
function currentDateTimeLocalValue(): string {
  const now = new Date();
  const pad = (value: number) => String(value).padStart(2, '0');
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(
    now.getDate()
  )}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
}
