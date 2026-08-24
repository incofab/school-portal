import type { Locator, Page } from '@playwright/test';
import {
  config,
  onboardingTutorialFixtures as fixtures,
  pace,
} from '../config';
import {
  chooseReactSelectOption,
  clickWithCursor,
  moveCursorTo,
  pauseLong,
  pauseMedium,
  pauseShort,
  reactSelectControl,
  typeForTutorial,
} from '../helpers/tutorial-actions';
import { Tutorial } from '../helpers/tutorial';

/**
 * "First-Time School Onboarding" walkthrough. Demonstrates the complete
 * real setup journey a brand-new school's admin follows the first time
 * they log in — this app's own dashboard already guides them through this
 * exact sequence via its "Setup Checklist" (see
 * `App\Support\SetupChecklistHandler` and `dashboard.setup-checklist`):
 *
 *   log in for the first time -> see the "unfinished setup" prompt on the
 *   dashboard -> set up the school's own profile and bank account -> work
 *   through the Setup Checklist itself: add classes, add subjects, add a
 *   teacher and assign them to a subject and a class, add a student -> add
 *   a guardian for that student (not itself a checklist item, but the
 *   natural next step once a student exists) -> return to the checklist to
 *   see every required item now marked done.
 *
 * Every field, button, and route used here was verified against the real
 * InstitutionController/BankAccountController/ClassificationController/
 * ClassificationGroupController/CourseController/UserController/
 * CourseTeacherController/StudentManagementController/
 * GuardianManagementController code paths and their Inertia pages —
 * nothing here is invented. This tutorial deliberately never switches
 * roles (no teacher/student/guardian login) — it is entirely the admin's
 * first-time setup experience, start to finish.
 *
 * Runs against its own isolated, empty institution (see
 * `App\Console\Commands\Tutorials\SeedOnboardingTutorialData` and
 * `onboardingTutorialFixtures`), not the shared "Tutorial Demo Academy"
 * used by the other tutorials — the whole point here is a school with
 * nothing configured yet, which the shared institution can't guarantee
 * once other tutorials have seeded data into it.
 */
export async function runSchoolOnboardingWalkthroughTutorial(
  page: Page
): Promise<void> {
  const tutorial = await Tutorial.create(page);

  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  const emailInput = page.getByLabel('Email address');
  const passwordInput = page.getByLabel('Password', { exact: true });
  const loginButton = page.getByRole('button', { name: 'Login', exact: true });
  await emailInput.waitFor({ state: 'visible' });
  await pauseMedium();

  await tutorial.announce(
    'Tutorial',
    'Setting Up Your New School',
    "Your school account has just been created. Let's walk through everything you need to configure, step by step, before your school is ready to use.",
    pace.long
  );

  await tutorial.focusOn([emailInput, passwordInput, loginButton]);
  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In as Your School Admin',
    description: 'Log in with the administrator account created for you.',
    target: emailInput,
    action: async () => {
      await typeForTutorial(page, emailInput, fixtures.adminEmail);
      await typeForTutorial(page, passwordInput, config.demoPassword);
      await clickWithCursor(page, loginButton);
    },
  });

  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  const accountMenuButton = page.getByRole('button', { name: 'Open menu' });
  await accountMenuButton.waitFor({ state: 'visible', timeout: 20000 });
  const instBaseUrl = page.url().replace(/\/dashboard(\/|$|\?).*$/, '');
  await pauseMedium();

  // ===========================================================
  // Part 1 — The Setup Checklist
  // ===========================================================
  await tutorial.announce(
    'Part 1 of 6',
    'Your Setup Checklist',
    'Because nothing has been configured yet, your dashboard flags this for you right away.',
    pace.long
  );

  const continueSetupLink = page.getByRole('link', { name: 'Continue Setup' });
  await continueSetupLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([continueSetupLink], { maxScale: 1.4 });
  await tutorial.step({
    eyebrow: 'Unfinished Setup',
    title: 'Open Your Setup Checklist',
    description:
      'This banner appears whenever required setup is still missing. Click Continue Setup to see exactly what’s left.',
    target: continueSetupLink,
    action: async () => clickWithCursor(page, continueSetupLink),
  });
  await tutorial.clearFocus();

  await page.waitForURL((url) => url.pathname.endsWith('/setup-checklist'), {
    timeout: 20000,
  });
  const checklistTable = page.locator('table');
  await checklistTable.waitFor({ state: 'visible' });
  await tutorial.focusOn([checklistTable], { maxScale: 1.2 });
  await tutorial.announce(
    'Setup Checklist',
    'One Row per Required Step',
    'Each row is something your school needs before it’s ready. Click "Add Now" on a row to go straight to it — we’ll work through them one at a time, starting with your school’s own information.',
    pace.long
  );
  await tutorial.clearFocus();

  // ===========================================================
  // Part 2 — School profile, bank account, and settings
  // ===========================================================
  await tutorial.announce(
    'Part 2 of 6',
    "Your School's Profile",
    'Before adding classes or students, let’s make sure your own school’s information is filled in — this appears across the dashboard, documents, and receipts.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/profile`, { waitUntil: 'networkidle' });
  // institution-profile.tsx gives these four fields an explicit `id` on a
  // raw `<Input>` instead of going through `InputForm`, which overrides
  // the id Chakra's FormControl would otherwise share with the FormLabel
  // — so `getByLabel` can't find them here, unlike every other form in
  // this app. Their ids are hardcoded in that file, so target those.
  const nameInput = page.locator('#name');
  const phoneInput = page.locator('#phone');
  const emailFieldInput = page.locator('#email');
  const addressInput = page.locator('#address');
  const websiteInput = page.getByLabel('Website URL');
  const saveProfileButton = page.getByRole('button', {
    name: 'Save',
    exact: true,
  });
  await nameInput.waitFor({ state: 'visible' });

  await tutorial.focusOn([nameInput, phoneInput, emailFieldInput]);
  await tutorial.step({
    eyebrow: 'School Identity',
    title: 'Set Your School Name',
    description:
      'This is the name shown throughout the app, and on printed documents.',
    target: nameInput,
    action: async () => {
      await typeForTutorial(page, nameInput, fixtures.institutionName);
    },
  });

  await tutorial.step({
    eyebrow: 'Contact Information',
    title: 'Add a Phone Number',
    description:
      'A phone number staff, guardians, and students can reach you on.',
    target: phoneInput,
    action: async () => {
      await typeForTutorial(page, phoneInput, '08012345678');
    },
  });

  await tutorial.step({
    eyebrow: 'Contact Information',
    title: 'Add an Email Address',
    description: 'Your school’s public contact email.',
    target: emailFieldInput,
    action: async () => {
      await typeForTutorial(
        page,
        emailFieldInput,
        'info@newhorizonacademy.example'
      );
    },
  });

  await tutorial.focusOn([addressInput, websiteInput, saveProfileButton]);
  await tutorial.step({
    eyebrow: 'Contact Information',
    title: 'Add Your Address',
    description: 'Shown on receipts and other printed documents.',
    target: addressInput,
    action: async () => {
      await typeForTutorial(page, addressInput, '12 Unity Road, Lagos');
    },
  });

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save Your Profile',
    description: 'Click Save to update your school’s information.',
    target: saveProfileButton,
    action: async () => clickWithCursor(page, saveProfileButton),
  });
  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle').catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Part 2 of 6',
    "Add the School's Bank Account",
    'Next, add a bank account so guardians and students have somewhere to send money for manual bank transfer payments.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/inst-bank-accounts`, {
    waitUntil: 'networkidle',
  });
  const newBankAccountButton = page.getByRole('link', {
    name: 'New',
    exact: true,
  });
  await newBankAccountButton.waitFor({ state: 'visible' });
  await tutorial.focusOn([newBankAccountButton], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'School Bank Accounts',
    title: 'Add a New Account',
    description: 'Click New to add your school’s bank account.',
    target: newBankAccountButton,
    action: async () => clickWithCursor(page, newBankAccountButton),
  });
  await tutorial.clearFocus();

  const bankNameControl = reactSelectControl(page, 'Bank Name');
  await bankNameControl.waitFor({ state: 'visible' });
  const accountNumberInput = page.getByLabel('Account Number');
  const validateButton = page.getByRole('button', {
    name: 'Validate',
    exact: true,
  });

  await tutorial.focusOn([bankNameControl, accountNumberInput, validateButton]);
  await tutorial.step({
    eyebrow: 'Bank Details',
    title: 'Choose the Bank',
    description: 'Search for and select your school’s bank.',
    target: bankNameControl,
    action: async () =>
      chooseReactSelectOption(page, bankNameControl, fixtures.bankName),
  });

  await tutorial.step({
    eyebrow: 'Bank Details',
    title: 'Enter the Account Number',
    description: 'Enter your school’s account number for this bank.',
    target: accountNumberInput,
    action: async () => {
      await typeForTutorial(
        page,
        accountNumberInput,
        fixtures.bankAccountNumber
      );
    },
  });

  await tutorial.step({
    eyebrow: 'Bank Details',
    title: 'Verify the Account',
    description:
      'Click Validate to confirm the account and automatically fill in the account name.',
    target: validateButton,
    action: async () => {
      await clickWithCursor(page, validateButton);
      await pauseMedium();
    },
  });

  const submitBankButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await tutorial.focusOn([accountNumberInput, submitBankButton]);

  await tutorial.announce(
    'Why This Matters',
    'This Enables Manual Bank Transfers',
    'Once a fee is set up, guardians and students who choose to pay by bank transfer will see this exact account — they send the money here, then record the payment for your school to confirm.',
    pace.long
  );

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Bank Account',
    description: 'Click Submit to save this account.',
    target: submitBankButton,
    action: async () => clickWithCursor(page, submitBankButton),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/inst-bank-accounts'), {
      timeout: 20000,
    })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Part 2 of 6',
    'One More Settings Page Worth Knowing',
    'The Settings page (under the Admin menu) is where your current academic term and session live, along with result and payment settings. It’s already set to sensible defaults — come back here any time your term or session changes.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/settings/create`, {
    waitUntil: 'networkidle',
  });
  const settingsHeading = page.getByText('Set Your Settings', { exact: true });
  await settingsHeading.waitFor({ state: 'visible' });
  await pauseLong();

  // ===========================================================
  // Part 3 — Academic structure: classes and subjects
  // ===========================================================
  await tutorial.announce(
    'Part 3 of 6',
    'Build Your Academic Structure',
    'Now for the checklist itself. First, your school needs classes for students to belong to.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/dashboard/setup-checklist`, {
    waitUntil: 'networkidle',
  });
  await clickAddNow(page, tutorial, 'Add Classes');

  await page.waitForURL(
    (url) => url.pathname.endsWith('/classifications/create'),
    {
      timeout: 20000,
    }
  );
  const addClassGroupButton = page.getByRole('button', {
    name: 'Add class group',
  });
  await addClassGroupButton.waitFor({ state: 'visible' });

  await tutorial.announce(
    'Classes Belong to a Class Group',
    'Create a Class Group First',
    'Class groups (like "Junior Secondary" or "Senior Secondary") organize your classes — create one before your first class.',
    pace.long
  );

  await tutorial.focusOn([addClassGroupButton], { maxScale: 1.4 });
  await tutorial.step({
    eyebrow: 'New Class',
    title: 'Create a Class Group',
    description:
      'Click the plus button to create a class group without leaving this page.',
    target: addClassGroupButton,
    action: async () => clickWithCursor(page, addClassGroupButton),
  });

  const classGroupTitleInput = page.getByLabel('Class Group Title');
  await classGroupTitleInput.waitFor({ state: 'visible' });
  const createClassGroupButton = page.getByRole('button', {
    name: 'Create',
    exact: true,
  });
  await tutorial.focusOn([classGroupTitleInput, createClassGroupButton], {
    maxScale: 1.4,
  });
  await tutorial.step({
    eyebrow: 'New Class Group',
    title: 'Name the Class Group',
    description: 'Give it a name that groups related classes together.',
    target: classGroupTitleInput,
    action: async () => {
      await typeForTutorial(
        page,
        classGroupTitleInput,
        fixtures.classGroupTitle
      );
    },
  });
  await tutorial.step({
    eyebrow: 'New Class Group',
    title: 'Save the Class Group',
    description: 'Click Create to save it — the page refreshes to pick it up.',
    target: createClassGroupButton,
    action: async () => clickWithCursor(page, createClassGroupButton),
  });
  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  await pauseMedium();

  const classGroupControl = reactSelectControl(page, 'Class Group');
  const classTitleInput = page.getByLabel('Class Title');
  const classSubmitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await classGroupControl.waitFor({ state: 'visible' });

  await tutorial.announce(
    'Class Group Ready',
    'Now Create Your First Class',
    `The page reloaded with "${fixtures.classGroupTitle}" ready to use.`,
    pace.medium
  );

  await tutorial.focusOn([
    classGroupControl,
    classTitleInput,
    classSubmitButton,
  ]);
  await tutorial.step({
    eyebrow: 'New Class',
    title: 'Choose the Class Group',
    description: `Select "${fixtures.classGroupTitle}", the group we just created.`,
    target: classGroupControl,
    action: async () =>
      chooseReactSelectOption(
        page,
        classGroupControl,
        fixtures.classGroupTitle
      ),
  });
  await tutorial.step({
    eyebrow: 'New Class',
    title: 'Name the Class',
    description: `Give the class a title — we’ll call this one "${fixtures.classTitles[0]}".`,
    target: classTitleInput,
    action: async () => {
      await typeForTutorial(page, classTitleInput, fixtures.classTitles[0]);
    },
  });

  const formTeacherControl = reactSelectControl(page, 'Form Teacher');
  await tutorial.focusOn([formTeacherControl, classSubmitButton]);
  await tutorial.step({
    eyebrow: 'New Class',
    title: 'Form Teacher Comes Later',
    description:
      'This assigns a form (class) teacher — we don’t have any teachers yet, so we’ll leave it and come back once one exists.',
    target: formTeacherControl,
  });

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Class',
    description: 'Click Submit to create the class.',
    target: classSubmitButton,
    action: async () => clickWithCursor(page, classSubmitButton),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/classifications'), {
      timeout: 20000,
    })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Class Created',
    'Add a Second Class',
    `Let's add "${fixtures.classTitles[1]}" too, using the same class group.`,
    pace.medium
  );

  const newClassButton = page.getByRole('link', { name: 'New', exact: true });
  await newClassButton.waitFor({ state: 'visible' });
  await tutorial.focusOn([newClassButton], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'List Classes',
    title: 'Start Another Class',
    description: 'Click New to add the second class.',
    target: newClassButton,
    action: async () => clickWithCursor(page, newClassButton),
  });
  await tutorial.clearFocus();

  await page.waitForURL(
    (url) => url.pathname.endsWith('/classifications/create'),
    {
      timeout: 20000,
    }
  );
  const classGroupControl2 = reactSelectControl(page, 'Class Group');
  const classTitleInput2 = page.getByLabel('Class Title');
  const classSubmitButton2 = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await classGroupControl2.waitFor({ state: 'visible' });
  await tutorial.focusOn([
    classGroupControl2,
    classTitleInput2,
    classSubmitButton2,
  ]);
  await tutorial.step({
    eyebrow: 'New Class',
    title: 'Choose the Class Group',
    description: `Select "${fixtures.classGroupTitle}" again.`,
    target: classGroupControl2,
    action: async () =>
      chooseReactSelectOption(
        page,
        classGroupControl2,
        fixtures.classGroupTitle
      ),
  });
  await tutorial.step({
    eyebrow: 'New Class',
    title: 'Name the Class',
    description: `We'll call this one "${fixtures.classTitles[1]}".`,
    target: classTitleInput2,
    action: async () => {
      await typeForTutorial(page, classTitleInput2, fixtures.classTitles[1]);
    },
  });
  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Class',
    description: 'Click Submit to create the second class.',
    target: classSubmitButton2,
    action: async () => clickWithCursor(page, classSubmitButton2),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/classifications'), {
      timeout: 20000,
    })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Classes Ready',
    `${fixtures.classTitles[0]} and ${fixtures.classTitles[1]} Are Set Up`,
    'Next, let’s add the subjects your school teaches.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/dashboard/setup-checklist`, {
    waitUntil: 'networkidle',
  });
  await clickAddNow(page, tutorial, 'Add Subjects');

  await page.waitForURL((url) => url.pathname.endsWith('/courses/create'), {
    timeout: 20000,
  });
  await createSubject(tutorial, page, fixtures.subjectTitles[0], '1');

  await page
    .waitForURL((url) => url.pathname.endsWith('/courses'), { timeout: 20000 })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Subject Created',
    'Add a Second Subject',
    `Now let's add "${fixtures.subjectTitles[1]}".`,
    pace.medium
  );

  const newSubjectButton = page.getByRole('link', { name: 'New', exact: true });
  await newSubjectButton.waitFor({ state: 'visible' });
  await tutorial.focusOn([newSubjectButton], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'List Subjects',
    title: 'Start Another Subject',
    description: 'Click New to add the second subject.',
    target: newSubjectButton,
    action: async () => clickWithCursor(page, newSubjectButton),
  });
  await tutorial.clearFocus();

  await page.waitForURL((url) => url.pathname.endsWith('/courses/create'), {
    timeout: 20000,
  });
  await createSubject(tutorial, page, fixtures.subjectTitles[1], '2');

  await page
    .waitForURL((url) => url.pathname.endsWith('/courses'), { timeout: 20000 })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Subjects Ready',
    'Your Academic Structure Is Taking Shape',
    `${fixtures.subjectTitles[0]} and ${fixtures.subjectTitles[1]} are ready. Next, let's add a teacher.`,
    pace.long
  );

  // ===========================================================
  // Part 4 — Teachers
  // ===========================================================
  await tutorial.announce(
    'Part 4 of 6',
    'Add Your First Teacher',
    'Every school needs teaching staff before subjects can be taught. Let’s add one.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/dashboard/setup-checklist`, {
    waitUntil: 'networkidle',
  });
  await clickAddNow(page, tutorial, 'Add Teachers / Staff');

  await page.waitForURL((url) => url.pathname.endsWith('/users/create'), {
    timeout: 20000,
  });
  const staffFirstNameInput = page.getByLabel('First Name');
  await staffFirstNameInput.waitFor({ state: 'visible' });
  const staffLastNameInput = page.getByLabel('Last Name');
  const staffEmailInput = page.getByLabel('Email', { exact: true });
  const staffPhoneInput = page.getByLabel('Phone', { exact: true });
  const staffGenderControl = reactSelectControl(page, 'Gender');
  const staffRoleControl = reactSelectControl(page, /^Role\b/);
  const staffSubmitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await tutorial.focusOn([
    staffFirstNameInput,
    staffLastNameInput,
    staffEmailInput,
    staffPhoneInput,
  ]);
  await tutorial.step({
    eyebrow: 'New Staff Member',
    title: "Enter the Teacher's Name",
    description: 'Fill in their first and last name.',
    target: staffFirstNameInput,
    action: async () => {
      await typeForTutorial(
        page,
        staffFirstNameInput,
        fixtures.teacherFirstName
      );
      await typeForTutorial(page, staffLastNameInput, fixtures.teacherLastName);
    },
  });
  await tutorial.step({
    eyebrow: 'New Staff Member',
    title: 'Add Their Contact Details',
    description:
      'An email and phone number so they can be reached, and can log in.',
    target: staffEmailInput,
    action: async () => {
      await typeForTutorial(page, staffEmailInput, fixtures.teacherEmail);
      await typeForTutorial(page, staffPhoneInput, fixtures.teacherPhone);
    },
  });

  await tutorial.focusOn([
    staffGenderControl,
    staffRoleControl,
    staffSubmitButton,
  ]);
  await tutorial.step({
    eyebrow: 'New Staff Member',
    title: 'Set Their Gender',
    target: staffGenderControl,
    description: 'Select Female.',
    action: async () =>
      chooseReactSelectOption(page, staffGenderControl, 'Female'),
  });
  await tutorial.step({
    eyebrow: 'New Staff Member',
    title: 'Choose Their Role',
    description: 'Select Teacher — this controls what they can access.',
    target: staffRoleControl,
    action: async () =>
      chooseReactSelectOption(page, staffRoleControl, 'Teacher'),
  });

  await tutorial.announce(
    'Default Password',
    'New Staff Start with the Password “password”',
    'Every new account starts with this same default password — tell them to change it after their first login.',
    pace.long
  );

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Teacher',
    description: 'Click Submit to create the account.',
    target: staffSubmitButton,
    action: async () => clickWithCursor(page, staffSubmitButton),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/index'), { timeout: 20000 })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Part 4 of 6',
    'Assign the Teacher to a Subject',
    'A teacher isn’t linked to anything yet — assigning them to a subject and class is what lets them record results and access their classes.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/dashboard/setup-checklist`, {
    waitUntil: 'networkidle',
  });
  await clickAddNow(page, tutorial, 'Assign teachers to subjects');

  await page.waitForURL(
    (url) => url.pathname.endsWith('/course-teachers/create'),
    {
      timeout: 20000,
    }
  );
  const teacherControl = reactSelectControl(page, 'Teacher');
  await teacherControl.waitFor({ state: 'visible' });
  // "Subject" is also this app's sidebar top-level nav label (which sits
  // earlier in the DOM than this form), so the first exact match is the
  // sidebar, not the form's own "Subject" field — skip to the 2nd match.
  // See cbt-exam-workflow.tutorial.ts for the same issue with "Add Subject".
  const assignSubjectControl = reactSelectControl(page, 'Subject', 1);
  const assignClassControl = reactSelectControl(page, 'Class');
  const assignSubmitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await tutorial.focusOn([
    teacherControl,
    assignSubjectControl,
    assignClassControl,
  ]);
  await tutorial.step({
    eyebrow: 'Assign Subject',
    title: 'Choose the Teacher',
    description: `Select ${fixtures.teacherFirstName} ${fixtures.teacherLastName}.`,
    target: teacherControl,
    action: async () =>
      chooseReactSelectOption(
        page,
        teacherControl,
        new RegExp(`${fixtures.teacherFirstName}.*${fixtures.teacherLastName}`)
      ),
  });
  await tutorial.step({
    eyebrow: 'Assign Subject',
    title: 'Choose the Subjects',
    description:
      'Select both subjects — you can assign more than one at a time.',
    target: assignSubjectControl,
    action: async () => {
      await chooseReactSelectOption(
        page,
        assignSubjectControl,
        fixtures.subjectTitles[0]
      );
      await chooseReactSelectOption(
        page,
        assignSubjectControl,
        fixtures.subjectTitles[1]
      );
    },
  });
  await tutorial.step({
    eyebrow: 'Assign Subject',
    title: 'Choose the Class',
    description: `Select "${fixtures.classTitles[0]}" — the class they’ll teach these subjects to.`,
    target: assignClassControl,
    action: async () =>
      chooseReactSelectOption(
        page,
        assignClassControl,
        fixtures.classTitles[0]
      ),
  });

  await tutorial.focusOn([assignSubmitButton]);
  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Assignment',
    description:
      'Click Submit to link the teacher to these subjects and class.',
    target: assignSubmitButton,
    action: async () => clickWithCursor(page, assignSubmitButton),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/index'), { timeout: 20000 })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Part 4 of 6',
    'Make Them a Form Teacher, Too',
    `Now that ${fixtures.teacherFirstName} exists, let's go back and make her the Form Teacher for ${fixtures.classTitles[0]} — the teacher responsible for that class.`,
    pace.long
  );

  await page.goto(`${instBaseUrl}/classifications`, {
    waitUntil: 'networkidle',
  });
  const firstClassRow = page.locator('tr', {
    hasText: fixtures.classTitles[0],
  });
  const editClassLink = firstClassRow.getByRole('link', { name: 'Edit Class' });
  await editClassLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([editClassLink], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'List Classes',
    title: `Edit ${fixtures.classTitles[0]}`,
    description: 'Click the edit icon on this class’s row.',
    target: editClassLink,
    action: async () => clickWithCursor(page, editClassLink),
  });
  await tutorial.clearFocus();

  await page.waitForURL((url) => url.pathname.endsWith('/edit'), {
    timeout: 20000,
  });
  const formTeacherEditControl = reactSelectControl(page, 'Form Teacher');
  await formTeacherEditControl.waitFor({ state: 'visible' });
  const classUpdateButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });
  await tutorial.focusOn([formTeacherEditControl, classUpdateButton]);
  await tutorial.step({
    eyebrow: 'Update Class',
    title: 'Choose the Form Teacher',
    description: `Select ${fixtures.teacherFirstName} ${fixtures.teacherLastName} — she’s now available since we added her as a teacher.`,
    target: formTeacherEditControl,
    action: async () =>
      chooseReactSelectOption(
        page,
        formTeacherEditControl,
        new RegExp(`${fixtures.teacherFirstName}.*${fixtures.teacherLastName}`)
      ),
  });
  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Change',
    description: 'Click Submit to confirm the form teacher.',
    target: classUpdateButton,
    action: async () => clickWithCursor(page, classUpdateButton),
  });
  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle').catch(() => undefined);
  await pauseMedium();

  // ===========================================================
  // Part 5 — Students and guardians
  // ===========================================================
  await tutorial.announce(
    'Part 5 of 6',
    'Add Your First Student',
    'With classes, subjects, and a teacher in place, your school is ready for its first student.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/dashboard/setup-checklist`, {
    waitUntil: 'networkidle',
  });
  await clickAddNow(page, tutorial, 'Add Students');

  await page.waitForURL((url) => url.pathname.endsWith('/students/create'), {
    timeout: 20000,
  });
  const studentFirstNameInput = page.getByLabel('First Name');
  await studentFirstNameInput.waitFor({ state: 'visible' });
  const studentLastNameInput = page.getByLabel('Last Name');
  const studentPhoneInput = page.getByLabel('Phone', { exact: true });
  const studentGenderControl = reactSelectControl(page, 'Gender');

  await tutorial.focusOn([
    studentFirstNameInput,
    studentLastNameInput,
    studentPhoneInput,
  ]);
  await tutorial.step({
    eyebrow: 'New Student',
    title: "Enter the Student's Name",
    description:
      'Fill in their name. Email is optional for students — many don’t have one yet.',
    target: studentFirstNameInput,
    action: async () => {
      await typeForTutorial(
        page,
        studentFirstNameInput,
        fixtures.studentFirstName
      );
      await typeForTutorial(
        page,
        studentLastNameInput,
        fixtures.studentLastName
      );
    },
  });
  await tutorial.step({
    eyebrow: 'New Student',
    title: 'Add a Phone Number',
    description: 'Optional, but useful for reaching the student directly.',
    target: studentPhoneInput,
    action: async () => {
      await typeForTutorial(page, studentPhoneInput, fixtures.studentPhone);
    },
  });

  const studentClassControl = reactSelectControl(page, /^Class\s*\*?$/);
  const guardianPhoneInput = page.getByLabel('Guardian Phone');
  const studentSubmitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await tutorial.focusOn([
    studentGenderControl,
    studentClassControl,
    guardianPhoneInput,
  ]);
  await tutorial.step({
    eyebrow: 'New Student',
    title: 'Set Their Gender',
    description: 'Select Male.',
    target: studentGenderControl,
    action: async () =>
      chooseReactSelectOption(page, studentGenderControl, 'Male'),
  });
  await tutorial.step({
    eyebrow: 'New Student',
    title: 'Assign a Class',
    description: `Select "${fixtures.classTitles[0]}" — the class this student belongs to.`,
    target: studentClassControl,
    action: async () =>
      chooseReactSelectOption(
        page,
        studentClassControl,
        fixtures.classTitles[0]
      ),
  });
  await tutorial.step({
    eyebrow: 'New Student',
    title: "Add a Guardian's Contact Number",
    description:
      'A quick contact number for now — next we’ll set up a full guardian account that can log in and see this student.',
    target: guardianPhoneInput,
    action: async () => {
      await typeForTutorial(page, guardianPhoneInput, fixtures.guardianPhone);
    },
  });

  await tutorial.focusOn([studentSubmitButton]);
  await tutorial.announce(
    'Student Code',
    'A Unique Code Is Generated Automatically',
    'Every student gets a unique code once saved — they’ll use it to log in, alongside their password.',
    pace.medium
  );

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Student',
    description: 'Click Submit to enroll this student.',
    target: studentSubmitButton,
    action: async () => clickWithCursor(page, studentSubmitButton),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/students'), { timeout: 20000 })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Part 5 of 6',
    'Link a Guardian to This Student',
    'A guardian account lets a parent log in, see their child’s records, and pay fees on their behalf — let’s create one.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/classifications`, {
    waitUntil: 'networkidle',
  });
  const classRowForGuardians = page.locator('tr', {
    hasText: fixtures.classTitles[0],
  });
  const rowMenuButton = classRowForGuardians.getByRole('button', {
    name: 'open file menu',
  });
  await rowMenuButton.waitFor({ state: 'visible' });
  await tutorial.focusOn([rowMenuButton], { maxScale: 1.5 });
  await tutorial.step({
    eyebrow: 'List Classes',
    title: 'Open This Class’s Menu',
    description: `Click the menu button on the ${fixtures.classTitles[0]} row.`,
    target: rowMenuButton,
    action: async () => clickWithCursor(page, rowMenuButton),
  });

  const recordGuardiansItem = page.getByRole('menuitem', {
    name: 'Record Guardians',
  });
  await recordGuardiansItem.waitFor({ state: 'visible' });
  await tutorial.focusOn([rowMenuButton, recordGuardiansItem], {
    maxScale: 1.4,
  });
  await tutorial.step({
    eyebrow: 'List Classes',
    title: 'Select Record Guardians',
    description:
      'This lists every student in this class who doesn’t have a guardian yet.',
    target: recordGuardiansItem,
    action: async () => clickWithCursor(page, recordGuardiansItem),
  });
  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  const guardianFirstNameInput = page.getByLabel('First Name');
  await guardianFirstNameInput.waitFor({ state: 'visible' });
  const guardianLastNameInput = page.getByLabel('Last Name');
  const guardianFormPhoneInput = page.getByLabel('Phone', { exact: true });
  const guardianFormEmailInput = page.getByLabel('Email', { exact: true });
  const guardianGenderSelect = page.getByLabel('Gender', { exact: true });
  const guardianRelationshipSelect = page.getByLabel('Relationship', {
    exact: true,
  });
  const guardianSubmitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await tutorial.focusOn([
    guardianFirstNameInput,
    guardianLastNameInput,
    guardianFormPhoneInput,
    guardianFormEmailInput,
  ]);
  await tutorial.step({
    eyebrow: 'Record Guardians',
    title: "Enter the Guardian's Name",
    description: `This is for ${fixtures.studentFirstName} ${fixtures.studentLastName} — shown at the top of this card.`,
    target: guardianFirstNameInput,
    action: async () => {
      await typeForTutorial(
        page,
        guardianFirstNameInput,
        fixtures.guardianFirstName
      );
      await typeForTutorial(
        page,
        guardianLastNameInput,
        fixtures.guardianLastName
      );
    },
  });
  await tutorial.step({
    eyebrow: 'Record Guardians',
    title: 'Add Their Contact Details',
    description:
      'A phone number and email so they can be reached, and can log in.',
    target: guardianFormPhoneInput,
    action: async () => {
      await typeForTutorial(
        page,
        guardianFormPhoneInput,
        fixtures.guardianPhone
      );
      await typeForTutorial(
        page,
        guardianFormEmailInput,
        fixtures.guardianEmail
      );
    },
  });

  await tutorial.focusOn([
    guardianGenderSelect,
    guardianRelationshipSelect,
    guardianSubmitButton,
  ]);
  await tutorial.step({
    eyebrow: 'Record Guardians',
    title: 'Set Their Gender',
    description: 'Select Female.',
    target: guardianGenderSelect,
    action: async () => {
      await moveCursorTo(page, guardianGenderSelect);
      await guardianGenderSelect.selectOption({ label: 'Female' });
      await pauseShort();
    },
  });
  await tutorial.step({
    eyebrow: 'Record Guardians',
    title: 'Set the Relationship',
    description: `How ${fixtures.guardianFirstName} relates to ${fixtures.studentFirstName} — select Parent.`,
    target: guardianRelationshipSelect,
    action: async () => {
      await moveCursorTo(page, guardianRelationshipSelect);
      await guardianRelationshipSelect.selectOption({ label: 'Parent' });
      await pauseShort();
    },
  });

  await tutorial.announce(
    'Why This Matters',
    'This Creates a Real Login for the Guardian',
    `${fixtures.guardianFirstName} will be able to log in — with the same default password, "password" — see ${fixtures.studentFirstName}’s records, and pay any fees on their behalf.`,
    pace.long
  );

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Guardian',
    description:
      'Click Submit to create the guardian and link them to this student.',
    target: guardianSubmitButton,
    action: async () => clickWithCursor(page, guardianSubmitButton),
  });
  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/guardians'), {
      timeout: 20000,
    })
    .catch(() => undefined);
  await pauseMedium();

  const attachStudentButton = page
    .getByRole('button', { name: 'Attach Student' })
    .first();
  await attachStudentButton.waitFor({ state: 'visible', timeout: 20000 });
  await tutorial.focusOn([attachStudentButton], { maxScale: 1.5 });
  await tutorial.announce(
    'One More Thing',
    'Guardians Can Have More Than One Child',
    'If this guardian has another child at your school, use "Attach Student" on their row to link them too — no need to create a second guardian account.',
    pace.long
  );
  await tutorial.clearFocus();

  // ===========================================================
  // Part 6 — Everything is set up
  // ===========================================================
  await tutorial.announce(
    'Part 6 of 6',
    'Back to the Checklist',
    'Let’s check back in on your Setup Checklist to see how far we’ve come.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/dashboard/setup-checklist`, {
    waitUntil: 'networkidle',
  });
  const finalChecklistTable = page.locator('table');
  await finalChecklistTable.waitFor({ state: 'visible' });
  await tutorial.focusOn([finalChecklistTable], { maxScale: 1.2 });
  await pauseLong();

  await tutorial.announce(
    'All Required Steps Are Done',
    'Your School Is Ready to Use',
    'Classes, Subjects, Students, Teachers, and subject assignments are all marked Done. "Add Fees" is optional — come back to it whenever you’re ready to start charging for things like tuition or levies.',
    pace.long
  );
  await tutorial.clearFocus();

  await tutorial.announce(
    'All Done',
    'Tutorial Complete',
    'You’ve now set up your school profile and bank account, built your academic structure, added a teacher and a student, and linked a guardian. Your school is ready to grow from here.',
    pace.long
  );
  await tutorial.dismiss();
}

/**
 * Finds the row on the Setup Checklist page matching `itemLabel` (e.g.
 * "Add Classes") and clicks its "Add Now" link. Only present on rows that
 * aren't done yet — this tutorial always calls it before completing that
 * item, so it's always there.
 */
async function clickAddNow(
  page: Page,
  tutorial: Tutorial,
  itemLabel: string
): Promise<void> {
  const row = page.locator('tr', { hasText: itemLabel });
  const addNowLink = row.getByRole('link', { name: 'Add Now', exact: true });
  await addNowLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([row], { maxScale: 1.4 });
  await tutorial.step({
    eyebrow: 'Setup Checklist',
    title: itemLabel,
    description: `Click "Add Now" on the "${itemLabel}" row.`,
    target: addNowLink,
    action: async () => clickWithCursor(page, addNowLink),
  });
  await tutorial.clearFocus();
}

/** Fills and submits one subject on the "Create Subject" form. */
async function createSubject(
  tutorial: Tutorial,
  page: Page,
  title: string,
  order: string
): Promise<void> {
  const titleInput = page.getByLabel('Subject title');
  await titleInput.waitFor({ state: 'visible' });
  const orderInput = page.getByLabel('Order', { exact: true });
  const submitButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  await tutorial.focusOn([titleInput, orderInput, submitButton]);
  await tutorial.step({
    eyebrow: 'New Subject',
    title: 'Name the Subject',
    description: `We'll call this one "${title}".`,
    target: titleInput,
    action: async () => typeForTutorial(page, titleInput, title),
  });
  await tutorial.step({
    eyebrow: 'New Subject',
    title: 'Set the Display Order',
    description:
      'Controls the order subjects appear in lists — lower numbers show first.',
    target: orderInput,
    action: async () => typeForTutorial(page, orderInput, order),
  });
  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Subject',
    description: 'Click Submit to create it.',
    target: submitButton,
    action: async () => clickWithCursor(page, submitButton),
  });
  await tutorial.clearFocus();
}
