import type { Locator, Page } from '@playwright/test';
import { runArtisanCommand } from '../helpers/artisan';
import { config, feeTutorialFixtures as fixtures, pace } from '../config';
import {
  chooseReactSelectOption,
  clickLabelText,
  clickWithCursor,
  hrefForLinkText,
  pause,
  pauseLong,
  pauseMedium,
  pauseShort,
  reactSelectControl,
  typeForTutorial,
  waitForFrame,
} from '../helpers/tutorial-actions';
import { Tutorial } from '../helpers/tutorial';

/**
 * "Fee Recording & Payment" walkthrough. Demonstrates the full, connected
 * workflow this app actually supports:
 *
 *   admin creates a fee -> targets a class -> configures the school's bank
 *   account -> a student pays part of it online, and sees the resulting
 *   receipt -> admin reviews the payment in Payment History -> a parent
 *   signs in, sees the same fee for their ward, and pays another part of
 *   the remaining balance by bank transfer.
 *
 * Every field, button, and route used here was verified against the real
 * FeeController/BankAccountController/StudentFeePaymentController/
 * ManualPaymentController/ReceiptController code paths — nothing here is
 * invented. The one deliberate exception: Monnify's sandbox checkout only
 * ever reports success after an *actual* bank transfer lands against its
 * one-time test account — clicking "I've transferred the money" alone
 * never completes it, in test mode or otherwise. We still drive that real
 * checkout on screen (nothing fake is shown there), then finish the
 * payment server-side the same way the real callback would — see
 * `tutorial:simulate-monnify-payment`'s docblock for the full reasoning.
 */
export async function runFeePaymentTutorial(page: Page): Promise<void> {
  const tutorial = await Tutorial.create(page);

  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  const emailInput = page.getByLabel('Email address');
  const passwordInput = page.getByLabel('Password', { exact: true });
  const loginButton = page.getByRole('button', { name: 'Login', exact: true });
  await emailInput.waitFor({ state: 'visible' });
  await pauseMedium();

  await tutorial.announce(
    'Tutorial',
    'Fee Recording & Payment',
    'See how a school records a fee, how a student and a parent can each pay part of it, and how the school reviews what has been paid.',
    pace.long
  );

  // The login card is the whole subject here — zoom in once and stay
  // there for all three fields instead of zooming per field.
  await tutorial.focusOn([emailInput, passwordInput, loginButton]);

  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In as the Admin',
    description:
      'Log in with an administrator account to manage fees and payments.',
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
  const instBaseUrl = page.url().replace(/\/dashboard(\/|$|\?).*$/, '');
  await pauseMedium();

  // ===========================================================
  // Part 1 — Create a fee
  // ===========================================================
  await tutorial.announce(
    'Part 1 of 5',
    'Create a Fee',
    'Fees are created and managed from Payments → Fees.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/fees`, { waitUntil: 'networkidle' });
  const newFeeButton = page.getByRole('link', { name: 'New', exact: true });
  await newFeeButton.waitFor({ state: 'visible' });

  await tutorial.step({
    eyebrow: 'Fees',
    title: 'Start a New Fee',
    description: 'Click New to record a fee.',
    target: newFeeButton,
    action: async () => {
      await clickWithCursor(page, newFeeButton);
    },
  });

  const feeTitleInput = page.getByLabel('Fee title');
  await feeTitleInput.waitFor({ state: 'visible' });
  const feeAmountInput = page.getByLabel('Amount').first();
  const paymentIntervalControl = reactSelectControl(page, 'Payment Interval');

  // Group 1 of this form: the fee's own title/amount/interval.
  await tutorial.focusOn([
    feeTitleInput,
    feeAmountInput,
    paymentIntervalControl,
  ]);

  await tutorial.step({
    eyebrow: 'Fee Details',
    title: 'Name the Fee',
    description:
      'Give the fee a clear title students and parents will recognize.',
    target: feeTitleInput,
    action: async () => {
      await typeForTutorial(page, feeTitleInput, fixtures.feeTitle);
    },
  });

  await tutorial.step({
    eyebrow: 'Fee Details',
    title: 'Set the Total Amount',
    description:
      'Enter the total amount students will be charged for this fee.',
    target: feeAmountInput,
    action: async () => {
      await typeForTutorial(page, feeAmountInput, '10000');
    },
  });

  await tutorial.step({
    eyebrow: 'Fee Details',
    title: 'Choose How Often It Repeats',
    description:
      "A one-time fee is charged once and doesn't repeat every term.",
    target: paymentIntervalControl,
    action: async () => {
      await chooseReactSelectOption(page, paymentIntervalControl, 'One Time');
    },
  });

  await tutorial.announce(
    'Line Items',
    'Break Down the Fee',
    'Add line items to show parents exactly what this fee covers.',
    pace.medium
  );

  const firstItemTitle = page.getByLabel('Title', { exact: true }).first();
  const firstItemAmount = page.getByLabel('Amount').nth(1);
  const addFeeItemButton = page.getByRole('button', { name: 'Add Fee Item' });

  // Group 2: the line items section (a new focus, further down the card).
  await tutorial.focusOn([firstItemTitle, firstItemAmount, addFeeItemButton]);

  await tutorial.step({
    eyebrow: 'Line Items',
    title: 'Add the First Item',
    description: 'Each item has its own title and amount.',
    target: firstItemTitle,
    action: async () => {
      await typeForTutorial(page, firstItemTitle, 'Textbooks');
      await typeForTutorial(page, firstItemAmount, '6000');
    },
  });

  await tutorial.step({
    eyebrow: 'Line Items',
    title: 'Add Another Item',
    description: 'Click the plus button to add as many items as you need.',
    target: addFeeItemButton,
    action: async () => {
      await clickWithCursor(page, addFeeItemButton);
      await pauseShort();
      const secondItemTitle = page.getByLabel('Title', { exact: true }).nth(1);
      const secondItemAmount = page.getByLabel('Amount').nth(2);
      // Re-fit now that the second row exists, so it's framed too.
      await tutorial.focusOn([
        firstItemTitle,
        secondItemTitle,
        secondItemAmount,
        addFeeItemButton,
      ]);
      await typeForTutorial(page, secondItemTitle, 'Uniform');
      await typeForTutorial(page, secondItemAmount, '4000');
    },
  });

  await tutorial.announce(
    'Line Items',
    'These Add Up to the Fee',
    'Textbooks and Uniform together make up the ₦10,000 total we set earlier.',
    pace.medium
  );

  const addCategoryButton = page.getByRole('button', { name: 'Add Category' });
  const submitFeeButton = page.getByRole('button', {
    name: 'Submit',
    exact: true,
  });

  // Group 3: the "who pays this" section through to Submit.
  await tutorial.focusOn([addCategoryButton, submitFeeButton]);

  await tutorial.step({
    eyebrow: 'Who Pays This?',
    title: 'Choose the Students',
    description:
      'Decide whether this fee applies to everyone, one class, a class group, or a student grouping.',
    target: addCategoryButton,
    action: async () => {
      await clickWithCursor(page, addCategoryButton);
      await pauseShort();
    },
  });

  const classCheckboxText = page.getByText('Class', { exact: true }).first();
  const okayButton = page.getByRole('button', { name: 'Okay', exact: true });

  // The modal is its own small card, floating above the form — a fresh
  // focus on just its contents.
  await tutorial.focusOn([classCheckboxText, okayButton], { maxScale: 1.4 });

  await tutorial.step({
    eyebrow: 'Who Pays This?',
    title: 'Target a Specific Class',
    description: `We'll charge this fee to the ${fixtures.className} class only.`,
    target: classCheckboxText,
    action: async () => {
      await clickLabelText(page, 'Class', 0);
    },
  });

  const classPickerControl = reactSelectControl(page, 'Class', 1);
  await tutorial.focusOn([classCheckboxText, classPickerControl, okayButton], {
    maxScale: 1.4,
  });

  await tutorial.step({
    eyebrow: 'Who Pays This?',
    title: 'Pick the Class',
    description: `Select ${fixtures.className} from the list of classes.`,
    target: classPickerControl,
    action: async () => {
      await chooseReactSelectOption(
        page,
        classPickerControl,
        fixtures.className
      );
    },
  });

  await clickWithCursor(page, okayButton);
  await pauseMedium();

  // Modal closed — back to the main form, framed on the Submit button.
  await tutorial.focusOn([submitFeeButton]);

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Fee',
    description: 'Click Submit to record the fee for JSS 1 students.',
    target: submitFeeButton,
    action: async () => {
      await clickWithCursor(page, submitFeeButton);
    },
  });

  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/fees'), { timeout: 20000 })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Fee Created',
    'The Fee Is Now Live',
    `${fixtures.className} students can now see and pay "${fixtures.feeTitle}".`,
    pace.long
  );

  // ===========================================================
  // Part 2 — Configure the school's bank account
  // ===========================================================
  await tutorial.announce(
    'Part 2 of 5',
    'Add a Bank Account',
    "Configure the school's bank account so students can pay by direct transfer.",
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

  await tutorial.step({
    eyebrow: 'School Bank Accounts',
    title: 'Add a New Account',
    description:
      'Click New to add the account students should transfer money into.',
    target: newBankAccountButton,
    action: async () => {
      await clickWithCursor(page, newBankAccountButton);
    },
  });

  const bankNameControl = reactSelectControl(page, 'Bank Name');
  await bankNameControl.waitFor({ state: 'visible' });
  const accountNumberInput = page.getByLabel('Account Number');
  const validateButton = page.getByRole('button', {
    name: 'Validate',
    exact: true,
  });

  // This whole form is compact enough to keep fully framed throughout.
  await tutorial.focusOn([bankNameControl, accountNumberInput, validateButton]);

  await tutorial.step({
    eyebrow: 'Bank Details',
    title: 'Choose the Bank',
    description: "Search for and select the school's bank.",
    target: bankNameControl,
    action: async () => {
      await chooseReactSelectOption(page, bankNameControl, fixtures.bankName);
    },
  });

  await tutorial.step({
    eyebrow: 'Bank Details',
    title: 'Enter the Account Number',
    description: "Enter the school's account number for this bank.",
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
      'Click Validate to confirm the account number and automatically fill in the account name.',
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
  // Re-fit lower on the form, now that Account Name/primary checkbox/Submit
  // are the relevant fields.
  await tutorial.focusOn([accountNumberInput, submitBankButton]);

  await clickLabelText(page, 'Make this your primary bank account');
  await pauseShort();

  await tutorial.step({
    eyebrow: 'Almost Done',
    title: 'Save the Bank Account',
    description: 'Click Submit to make this account available to students.',
    target: submitBankButton,
    action: async () => {
      await clickWithCursor(page, submitBankButton);
    },
  });

  await tutorial.clearFocus();

  await page
    .waitForURL((url) => url.pathname.endsWith('/inst-bank-accounts'), {
      timeout: 20000,
    })
    .catch(() => undefined);
  await pauseMedium();

  await tutorial.announce(
    'Bank Account Saved',
    'Students Can Now See These Details',
    'This account will appear whenever a student chooses to pay by bank transfer.',
    pace.long
  );

  // ===========================================================
  // Switch roles: admin -> student
  // ===========================================================
  await tutorial.announce(
    'Switching Roles',
    'Now, as the Student',
    "Let's log out and sign in as a student to pay part of this fee.",
    pace.long
  );

  await logout(tutorial, page, adminAccountMenuButton);

  // ===========================================================
  // Part 3 — Student pays part of the fee online
  // ===========================================================
  // Logging out lands back on the main login page (see `logout`) — click
  // its "Student Login" link on screen rather than navigating straight to
  // /student/login by URL, so a viewer sees how to actually get there.
  const studentLoginLink = page.getByRole('link', {
    name: 'Student Login',
    exact: true,
  });
  await studentLoginLink.waitFor({ state: 'visible' });
  await tutorial.focusOn([studentLoginLink]);

  await tutorial.step({
    eyebrow: 'Switching Roles',
    title: 'Go to Student Login',
    description: 'Click Student Login on the main login page.',
    target: studentLoginLink,
    action: async () => {
      await clickWithCursor(page, studentLoginLink);
    },
  });

  await tutorial.clearFocus();
  await page.waitForURL(/\/student\/login(\/|$|\?)/, { timeout: 20000 });

  const studentIdInput = page.getByLabel('Student Id');
  const studentLoginButton = page.getByRole('button', {
    name: 'Login',
    exact: true,
  });
  await studentIdInput.waitFor({ state: 'visible' });
  await tutorial.focusOn([studentIdInput, studentLoginButton]);

  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In as the Student',
    description: 'Students log in with their Student Id, not an email address.',
    target: studentIdInput,
    action: async () => {
      await typeForTutorial(page, studentIdInput, fixtures.studentCode);
      await clickWithCursor(page, studentLoginButton);
    },
  });

  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  const studentAccountMenuButton = page.getByRole('button', {
    name: 'Open menu',
  });
  await studentAccountMenuButton.waitFor({ state: 'visible', timeout: 20000 });
  await pauseMedium();

  await tutorial.announce(
    'Part 3 of 5',
    'Pay Part of a Fee Online',
    'Students see any fee assigned to their class from Payments → Pay Fees.',
    pace.long
  );

  const payFeesHref = await hrefForLinkText(page, 'Pay Fees');
  await page.goto(payFeesHref, { waitUntil: 'networkidle' });

  const feeCategoryControl = reactSelectControl(page, 'Fee Category');
  await feeCategoryControl.waitFor({ state: 'visible' });
  const studentAmountInput = page.getByLabel('Amount', { exact: true });
  const payNowButton = page.getByRole('button', {
    name: 'Pay Now',
    exact: true,
  });

  await tutorial.focusOn([
    feeCategoryControl,
    studentAmountInput,
    payNowButton,
  ]);

  await tutorial.step({
    eyebrow: 'Pay Fees',
    title: 'Select the Fee',
    description: `Choose "${fixtures.feeTitle}" — the fee the school just created for this class.`,
    target: feeCategoryControl,
    action: async () => {
      await chooseReactSelectOption(
        page,
        feeCategoryControl,
        new RegExp(fixtures.feeTitle)
      );
    },
  });

  await tutorial.step({
    eyebrow: 'Pay Part of It',
    title: 'Choose How Much to Pay',
    description:
      'The full fee is ₦10,000, but a student can pay any part of it — let’s pay the ₦6,000 Textbooks portion now.',
    target: studentAmountInput,
    action: async () => {
      await typeForTutorial(page, studentAmountInput, '6000');
    },
  });

  await tutorial.announce(
    'Instant Payment',
    'Online Payment Is Selected by Default',
    'This sends the student to our secure payment partner to pay by card, bank, or transfer.',
    pace.medium
  );

  await tutorial.step({
    eyebrow: 'Instant Payment',
    title: 'Start the Online Payment',
    description: 'Click Pay Now to continue to the payment provider.',
    target: payNowButton,
    action: async () => {
      await clickWithCursor(page, payNowButton);
    },
  });

  await tutorial.clearFocus();

  await page.waitForURL(/\/monnify\/checkout/, { timeout: 20000 });
  const monnifyReference = new URL(page.url()).searchParams.get('reference');
  await page
    .locator('iframe')
    .first()
    .waitFor({ state: 'visible', timeout: 20000 })
    .catch(() => undefined);
  await pauseLong();

  const transferConfirmed = await completeMonnifySandboxCheckout(page);

  await tutorial.announce(
    'Safe Stopping Point',
    'Handing Off to the Payment Provider',
    "This is our payment partner's real checkout — a viewer would enter their bank transfer here. We don't enter real payment details in this recording.",
    pace.long
  );

  if (monnifyReference && !transferConfirmed) {
    // Monnify's sandbox only ever reports success once an actual (test)
    // bank transfer lands against the one-time account it generates —
    // there is no way to fabricate that transfer from this app. We finish
    // the payment the same way the real callback would, server-side, so
    // the rest of this tutorial can show what a completed payment looks
    // like. See tutorial:simulate-monnify-payment's docblock.
    await runArtisanCommand([
      'tutorial:simulate-monnify-payment',
      monnifyReference,
    ]).catch((error) => console.error('[tutorial] simulate failed:', error));
  }

  const studentReceiptsHref = payFeesHref.replace(
    /\/fee-payments\/create$/,
    '/receipts'
  );
  await page.goto(studentReceiptsHref, { waitUntil: 'networkidle' });
  await pauseMedium();

  await tutorial.announce(
    'Payment Successful',
    'View the Receipt',
    'Once a payment goes through, it shows up here with what was paid and what — if anything — is still owed.',
    pace.long
  );

  const studentPrintLink = page.getByRole('link', {
    name: 'Print',
    exact: true,
  });
  await studentPrintLink.waitFor({ state: 'visible', timeout: 20000 });
  // A single, small action on an otherwise wide table — a light zoom on
  // just this row/button still helps a mobile viewer see it clearly.
  await tutorial.focusOn([studentPrintLink], { maxScale: 1.5 });

  await tutorial.step({
    eyebrow: 'My Receipts',
    title: 'Open the Receipt',
    description: 'Click Print to view the full receipt for this payment.',
    target: studentPrintLink,
    action: async () => {
      await clickWithCursor(page, studentPrintLink);
    },
  });

  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  await pauseLong();

  // The receipt print view uses a bare print layout with no account menu —
  // return to a normal dashboard page before trying to log out from it.
  await page.goto(studentReceiptsHref, { waitUntil: 'networkidle' });
  await studentAccountMenuButton.waitFor({ state: 'visible', timeout: 20000 });

  // ===========================================================
  // Switch roles: student -> admin
  // ===========================================================
  await tutorial.announce(
    'Switching Roles',
    'Now, as the School',
    "Let's log out and sign back in as the admin to review this payment.",
    pace.long
  );

  await logout(tutorial, page, studentAccountMenuButton);

  // ===========================================================
  // Part 4 — Admin reviews payment history
  // ===========================================================
  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  await emailInput.waitFor({ state: 'visible' });
  await tutorial.focusOn([emailInput, passwordInput, loginButton]);

  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In as the Admin',
    description: "Let's check the school's payment history.",
    target: emailInput,
    action: async () => {
      await typeForTutorial(page, emailInput, config.demoEmail);
      await typeForTutorial(page, passwordInput, config.demoPassword);
      await clickWithCursor(page, loginButton);
    },
  });

  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  await adminAccountMenuButton.waitFor({ state: 'visible', timeout: 20000 });
  await pauseMedium();

  await tutorial.announce(
    'Part 4 of 5',
    'Review Payment History',
    'The school can see every payment made from Payments → Receipts.',
    pace.long
  );

  await page.goto(`${instBaseUrl}/receipts`, { waitUntil: 'networkidle' });
  const adminViewLink = page
    .getByRole('link', { name: 'View', exact: true })
    .first();
  await adminViewLink.waitFor({ state: 'visible', timeout: 20000 });
  await tutorial.focusOn([adminViewLink], { maxScale: 1.5 });

  await tutorial.step({
    eyebrow: 'Payment History',
    title: 'This Student Has Paid',
    description:
      "The list shows what's been paid and what's still owed. Click View to see the full receipt.",
    target: adminViewLink,
    action: async () => {
      await clickWithCursor(page, adminViewLink);
    },
  });

  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  await adminAccountMenuButton.waitFor({ state: 'visible', timeout: 20000 });
  await pauseLong();

  await tutorial.announce(
    'Switching Roles',
    'Now, as a Parent',
    "Let's log out and sign in as this student's parent to pay the rest.",
    pace.long
  );

  await logout(tutorial, page, adminAccountMenuButton);

  // ===========================================================
  // Part 5 — Guardian pays part of the remaining balance
  // ===========================================================
  await page.goto(`${config.baseUrl}/login`, { waitUntil: 'networkidle' });
  await emailInput.waitFor({ state: 'visible' });
  await tutorial.focusOn([emailInput, passwordInput, loginButton]);

  await tutorial.step({
    eyebrow: 'Sign In',
    title: 'Sign In as the Parent',
    description: 'Parents and guardians sign in the same way admins do.',
    target: emailInput,
    action: async () => {
      await typeForTutorial(page, emailInput, fixtures.guardianEmail);
      await typeForTutorial(page, passwordInput, config.demoPassword);
      await clickWithCursor(page, loginButton);
    },
  });

  await page.waitForURL(/\/dashboard(\/|$|\?)/, { timeout: 20000 });
  const guardianAccountMenuButton = page.getByRole('button', {
    name: 'Open menu',
  });
  await guardianAccountMenuButton.waitFor({
    state: 'visible',
    timeout: 20000,
  });
  await pauseMedium();

  await tutorial.announce(
    'Part 5 of 5',
    "See Your Child's Fees",
    "A parent's dashboard lists their children — from there they can view and pay any of their fees.",
    pace.long
  );

  const studentsHref = await hrefForLinkText(page, 'Students');
  await page.goto(studentsHref, { waitUntil: 'networkidle' });

  const feesReceiptsButton = page.getByRole('link', {
    name: 'Fees & Receipts',
    exact: true,
  });
  await feesReceiptsButton.waitFor({ state: 'visible' });
  // The ward's card is a small, self-contained unit on the page — worth
  // zooming into on its own.
  await tutorial.focusOn([feesReceiptsButton], { maxScale: 1.5 });

  await tutorial.step({
    eyebrow: 'My Students',
    title: "Open Your Child's Fees",
    description: 'Click Fees & Receipts on your child’s card.',
    target: feesReceiptsButton,
    action: async () => {
      await clickWithCursor(page, feesReceiptsButton);
    },
  });

  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  await pauseMedium();

  await tutorial.announce(
    'Balance Remaining',
    'The Rest of the Fee Is Still Owed',
    'The Textbooks portion is already paid. As a parent, you can pay the full remaining balance — or just part of it.',
    pace.long
  );

  const guardianPayFeesLink = page.getByRole('link', {
    name: 'Pay Fees',
    exact: true,
  });
  await tutorial.focusOn([guardianPayFeesLink], { maxScale: 1.5 });

  await tutorial.step({
    eyebrow: 'My Receipts',
    title: 'Open Pay Fees',
    description: 'Click Pay Fees to make a payment for your child.',
    target: guardianPayFeesLink,
    action: async () => {
      await clickWithCursor(page, guardianPayFeesLink);
    },
  });

  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle');
  const guardianFeeCategoryControl = reactSelectControl(page, 'Fee Category');
  await guardianFeeCategoryControl.waitFor({ state: 'visible' });
  const guardianAmountInput = page.getByLabel('Amount', { exact: true });
  const guardianPaymentMethodControl = reactSelectControl(
    page,
    'Payment Method'
  );

  await tutorial.focusOn([
    guardianFeeCategoryControl,
    guardianAmountInput,
    guardianPaymentMethodControl,
  ]);

  await tutorial.step({
    eyebrow: 'Pay Fees',
    title: 'Select the Fee',
    description: `Choose "${fixtures.feeTitle}" for your child.`,
    target: guardianFeeCategoryControl,
    action: async () => {
      await chooseReactSelectOption(
        page,
        guardianFeeCategoryControl,
        new RegExp(fixtures.feeTitle)
      );
    },
  });

  await tutorial.step({
    eyebrow: 'Pay Part of It',
    title: 'Choose How Much to Pay',
    description:
      "This defaults to the full fee — let's pay just ₦2,500 of what's left by bank transfer instead.",
    target: guardianAmountInput,
    action: async () => {
      await typeForTutorial(page, guardianAmountInput, '2500');
    },
  });

  await tutorial.step({
    eyebrow: 'Choose How to Pay',
    title: 'Select Manual Payment',
    description: 'Manual Payment lets a parent pay by direct bank transfer.',
    target: guardianPaymentMethodControl,
    action: async () => {
      await chooseReactSelectOption(
        page,
        guardianPaymentMethodControl,
        'Manual Payment'
      );
    },
  });

  await tutorial.announce(
    'Manual Payment',
    "Here Are the School's Bank Details",
    'Send the transfer to any of the accounts shown, then continue to record the payment.',
    pace.long
  );

  const guardianContinueButton = page.getByRole('button', {
    name: 'Continue',
    exact: true,
  });
  await tutorial.focusOn([guardianContinueButton], { maxScale: 1.4 });

  await tutorial.step({
    eyebrow: 'Manual Payment',
    title: 'Continue to Payment Details',
    description: 'Click Continue once the transfer has been made.',
    target: guardianContinueButton,
    action: async () => {
      await clickWithCursor(page, guardianContinueButton);
    },
  });

  await tutorial.clearFocus();

  await page.waitForURL(/\/manual-payments\//, { timeout: 20000 });
  await pauseMedium();

  const guardianBankPaidToControl = reactSelectControl(page, 'Bank Paid To*');
  await guardianBankPaidToControl.waitFor({ state: 'visible' });
  const guardianDepositorInput = page.getByLabel('Depositor Name [Optional]');
  const guardianNoteInput = page.getByLabel('Note [Optional]');
  const guardianSaveButton = page.getByRole('button', {
    name: 'Save Details',
    exact: true,
  });

  await tutorial.focusOn([
    guardianBankPaidToControl,
    guardianDepositorInput,
    guardianNoteInput,
    guardianSaveButton,
  ]);

  await tutorial.step({
    eyebrow: 'Payment Details',
    title: 'Choose the Bank You Paid Into',
    description: 'Match this to the account the transfer was actually sent to.',
    target: guardianBankPaidToControl,
    action: async () => {
      await chooseReactSelectOption(
        page,
        guardianBankPaidToControl,
        new RegExp(fixtures.bankName)
      );
    },
  });

  await tutorial.step({
    eyebrow: 'Payment Details',
    title: 'Add the Depositor Name',
    description: 'Optional, but it helps the school match the transfer faster.',
    target: guardianDepositorInput,
    action: async () => {
      await typeForTutorial(page, guardianDepositorInput, 'Tutorial Guardian');
    },
  });

  await tutorial.step({
    eyebrow: 'Payment Details',
    title: 'Leave a Note',
    description: 'Optional — add any extra detail about the payment.',
    target: guardianNoteInput,
    action: async () => {
      await typeForTutorial(
        page,
        guardianNoteInput,
        'Partial payment from parent'
      );
    },
  });

  await tutorial.step({
    eyebrow: 'Payment Details',
    title: 'Save Your Payment Details',
    description: 'Click Save Details to submit this for the school to confirm.',
    target: guardianSaveButton,
    action: async () => {
      await clickWithCursor(page, guardianSaveButton);
    },
  });

  await tutorial.clearFocus();

  await page.waitForLoadState('networkidle').catch(() => undefined);
  await pauseLong();

  await tutorial.announce(
    'Payment Submitted',
    'Awaiting Confirmation',
    "This payment is now awaiting confirmation from the school, the same way the student's transfer was.",
    pace.long
  );

  // ===========================================================
  // Outro
  // ===========================================================
  await tutorial.dismiss();

  await tutorial.announce(
    'All Done',
    'Tutorial Complete',
    'You now know how a fee is created, how a student and a parent can each pay part of it — online or by transfer — and how the school reviews every payment.',
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
  // Re-fit now that the menu (and Logout) is visible, so both the button
  // and the item it opened stay framed together.
  await tutorial.focusOn([accountMenuButton, logoutItem], { maxScale: 1.4 });

  await clickWithCursor(page, logoutItem);
  await tutorial.clearFocus();

  await page.waitForURL(/\/login(\/|$|\?)/, { timeout: 20000 });
  await pauseMedium();
}

/**
 * Drives the real Monnify sandbox checkout on screen: waits for the
 * doubly-nested widget iframe, clicks "I've transferred the money" (retrying
 * once through "Try again with Transfer" if the sandbox's account
 * generation glitches), and reports whether Monnify itself ever redirected
 * us away — which in practice it won't, since no real transfer happened
 * (see the caller for what happens next).
 */
async function completeMonnifySandboxCheckout(page: Page): Promise<boolean> {
  try {
    const widgetFrame = await waitForFrame(page, 'checkout/MNFY', 15000);

    const tryAgain = widgetFrame.getByText('Try again with Transfer');
    if (await tryAgain.count().catch(() => 0)) {
      await tryAgain.click().catch(() => undefined);
      await pause(pace.medium);
    }

    const transferButton = widgetFrame.getByText('transferred the money');
    await transferButton.waitFor({ state: 'visible', timeout: 10000 });
    await pauseMedium();
    await transferButton.click();
  } catch (error) {
    console.error(
      '[tutorial] Could not interact with the Monnify widget:',
      error
    );
    return false;
  }

  // Give Monnify a real chance to redirect us away on its own — it won't,
  // without an actual bank transfer, but we don't assume that.
  try {
    await page.waitForURL((url) => !url.href.includes('monnify'), {
      timeout: 8000,
    });
    return true;
  } catch {
    return false;
  }
}
