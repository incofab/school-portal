export interface DashboardExplanation {
  key: string;
  title: string;
  summary: string;
  pathPatterns: RegExp[];
  whenToUse: string[];
  steps: string[];
  tips: string[];
  related?: string[];
}

const institutionPath = /^\/institutions\/[^/]+/;

export const dashboardExplanations: DashboardExplanation[] = [
  {
    key: 'dashboard',
    title: 'Institution Dashboard',
    summary:
      'The dashboard is the control center for the current institution. It summarizes activity, highlights items that need attention, and links users to the most common academic, finance, communication, and setup workflows.',
    pathPatterns: [/\/dashboard\/?$/],
    whenToUse: [
      'Review institution-wide statistics and recent performance trends.',
      'Open urgent work such as pending manual payments, unread chats, setup reminders, and account funding actions.',
      'Navigate quickly to students, staff, classes, subjects, results, payments, exams, and communications.',
    ],
    steps: [
      'Start with the attention cards at the top; they show work that needs immediate action.',
      'Use the dashboard tiles to enter the feature area you want to manage.',
      'Check the charts and counts before making operational decisions, and use the refresh action when you need the latest dashboard statistics.',
      'For financial activity, verify wallet balance, reserved accounts, and payment review status before recording or approving payments.',
    ],
    tips: [
      'The dashboard follows the selected academic session and term shown in the header.',
      'If expected data is missing, confirm that the correct institution, session, term, and user role are active.',
    ],
    related: ['Setup checklist', 'Payments', 'Chats', 'Reports'],
  },
  {
    key: 'staff-users',
    title: 'Staff and User Management',
    summary:
      'This area manages institution users, staff profiles, roles, account access, ID cards, and staff-related records. It controls who can enter the dashboard and what each person is allowed to do.',
    pathPatterns: [
      /\/users(\/|$)/,
      /\/staff(\/|$)/,
      /\/idcards(\/|$)/,
      /\/inst-user-bank-accounts(\/|$)/,
    ],
    whenToUse: [
      'Create or update staff, teacher, accountant, guardian, student, or alumni user records.',
      'Assign the correct role so users see only the features they should manage.',
      'Maintain staff bank accounts, profile information, account status, and ID card outputs.',
    ],
    steps: [
      'Use the staff or users list to search for an existing person before creating a duplicate record.',
      'When adding staff, fill the personal details first, then assign the role that matches the person work in the institution.',
      'For teachers, connect them to subjects through the Subject Teachers page so result entry and lesson workflows are scoped correctly.',
      'Review suspension status and role changes carefully because they affect login access and permissions immediately.',
    ],
    tips: [
      'Use clear phone numbers and email addresses because they are reused in login, guardian, messaging, and payment workflows.',
      'When a user cannot see a page, first confirm their active institution role and whether they are in the correct institution.',
    ],
    related: ['Subject Teachers', 'Roles', 'Bank Accounts', 'Activity Logs'],
  },
  {
    key: 'students',
    title: 'Student Lifecycle',
    summary:
      'The student area covers enrollment, student profiles, class placement, guardians, ID cards, academic records, payment history, and student-facing result access.',
    pathPatterns: [
      /\/students(\/|$)/,
      /\/student-class-movements(\/|$)/,
      /\/class-students(\/|$)/,
    ],
    whenToUse: [
      'Register new students or update an existing student profile.',
      'Move, promote, or review students across classes and sessions.',
      'Open student-specific payments, results, guardians, evaluations, and ID outputs.',
    ],
    steps: [
      'Confirm the current academic session and term before creating or moving student records.',
      'Create or update the student biodata, then assign the correct class or division.',
      'Attach guardians where required so parent access, communication, and dependent views work properly.',
      'Use class movement and promotion pages for bulk class changes instead of editing many students one by one.',
    ],
    tips: [
      'Class placement affects attendance, results, fees, reports, and guardian views.',
      'For missing students in result or payment pages, check that the student is active in the selected class/session.',
    ],
    related: ['Guardians', 'Classifications', 'Results', 'Fees'],
  },
  {
    key: 'guardians',
    title: 'Guardians and Dependents',
    summary:
      'Guardian pages connect parents or sponsors to students. This enables guardian dashboards, dependent views, communication, payment tracking, and result access where allowed.',
    pathPatterns: [/\/guardians(\/|$)/, /\/dependents(\/|$)/],
    whenToUse: [
      'Link a guardian account to one or more students.',
      'Review the dependents a guardian can access.',
      'Fix parent access issues by confirming the guardian-student relationship.',
    ],
    steps: [
      'Search for the guardian record and open the assign-student action.',
      'Select the correct student, confirm the relationship, and save the assignment.',
      'For guardian dashboards, verify that the guardian is logged into the same institution where the dependent exists.',
      'Remove outdated links when a guardian should no longer access a student record.',
    ],
    tips: [
      'Guardian access depends on the relationship link, not just matching phone numbers.',
      'Keep student and guardian contact data accurate because communication and result activation can depend on it.',
    ],
    related: ['Students', 'Messages', 'Results', 'Payments'],
  },
  {
    key: 'classes',
    title: 'Classes, Groups, and Divisions',
    summary:
      'Class structures define how students, subjects, attendance, fees, results, timetables, and reports are grouped. This area covers classes, class groups, divisions, and class movement records.',
    pathPatterns: [
      /\/classifications(\/|$)/,
      /\/classification-groups(\/|$)/,
      /\/class-divisions(\/|$)/,
      /\/promote-students(\/|$)/,
    ],
    whenToUse: [
      'Create or update class levels such as JSS 1, Primary 4, or SS 2.',
      'Organize classes into groups and divisions for reports, timetables, or result processing.',
      'Promote students or track movement between classes.',
    ],
    steps: [
      'Set up class groups first when your institution uses grouped class structures.',
      'Create classes with clear titles and attach them to the right group where applicable.',
      'Use divisions to separate arms or streams, then add the relevant classes to each division.',
      'For promotions, review the source and destination class carefully before submitting bulk movements.',
    ],
    tips: [
      'Changing class structures can affect result sheets, attendance registers, subject mappings, and fee assignments.',
      'Use bulk upload only after confirming the template columns match the current setup.',
    ],
    related: ['Students', 'Subjects', 'Attendance', 'Results'],
  },
  {
    key: 'subjects',
    title: 'Subjects and Subject Teachers',
    summary:
      'Subject pages manage courses, class-subject mappings, teacher assignments, practice questions, and result recording access. Teachers usually work from the subject teacher assignment connected to their user account.',
    pathPatterns: [
      /\/courses(\/|$)/,
      /\/course-teachers(\/|$)/,
      /\/practice-(questions|progress)(\/|$)/,
      /\/topics(\/|$)/,
    ],
    whenToUse: [
      'Create subjects and connect them to the classes where they are taught.',
      'Assign teachers to subjects and classes for result entry and teaching workflows.',
      'Manage topics, subtopics, question banks, and student practice records.',
    ],
    steps: [
      'Create the subject with a clear name and code where applicable.',
      'Map the subject to the relevant classes so it appears in class result and teaching pages.',
      'Assign subject teachers to the correct class and subject combination.',
      'When recording results, verify the class, subject, session, term, and assessment setup before entering scores.',
    ],
    tips: [
      'Teacher result access depends on Subject Teachers, not only the Teacher role.',
      'Keep subject names consistent because they appear on report sheets, transcripts, lesson plans, and exams.',
    ],
    related: ['Assessments', 'Question Bank', 'Lesson Plans', 'Results'],
  },
  {
    key: 'curriculum',
    title: 'Curriculum, Lessons, and Learning Content',
    summary:
      'Curriculum pages help teachers plan and document teaching work. They include schemes of work, lesson plans, lesson notes, topics, subtopics, libraries, assignments, and live classes.',
    pathPatterns: [
      /\/scheme-of-works(\/|$)/,
      /\/lesson-plans(\/|$)/,
      /\/lesson-notes(\/|$)/,
      /\/libraries(\/|$)/,
      /\/assignments(\/|$)/,
      /\/live-classes(\/|$)/,
    ],
    whenToUse: [
      'Prepare scheme coverage and lesson documentation for a class and subject.',
      'Share learning materials, assignment instructions, and live class links.',
      'Review student assignment submissions and attached documents.',
    ],
    steps: [
      'Select the right subject, class, session, and term before creating learning content.',
      'Create schemes or lesson plans with topic coverage, objectives, and expected activities.',
      'Attach lesson notes, files, or library resources where students and staff need supporting material.',
      'For assignments, set the due date, instructions, attachments, and review submissions from the submissions page.',
    ],
    tips: [
      'Consistent topic and class selection makes curriculum reports easier to interpret.',
      'Use the library for reusable material and assignments for time-bound student work.',
    ],
    related: ['Subjects', 'Assignments', 'Live Classes', 'Topics'],
  },
  {
    key: 'attendance',
    title: 'Attendance and Attendance Reports',
    summary:
      'Attendance pages record daily or class-based presence data and produce reports for monitoring student participation.',
    pathPatterns: [/\/attendances(\/|$)/, /\/attendance-report(\/|$)/],
    whenToUse: [
      'Mark class attendance for a date or activity period.',
      'Review attendance trends for students, classes, or sessions.',
      'Print or export attendance registers for administration.',
    ],
    steps: [
      'Choose the class, date, session, and term before creating an attendance record.',
      'Mark each student accurately, then submit once the register has been reviewed.',
      'Use the report pages to filter by student, class, or date range.',
      'Correct mistakes from the relevant attendance record when permissions allow edits.',
    ],
    tips: [
      'Attendance reports depend on correct class placement for the selected period.',
      'Avoid duplicate registers for the same class and date unless the workflow intentionally separates periods.',
    ],
    related: ['Students', 'Classes', 'Reports'],
  },
  {
    key: 'assessments',
    title: 'Assessments and Learning Evaluations',
    summary:
      'Assessment pages define how marks are collected, weighted, inserted, and shown in results. Learning evaluations capture non-score comments or domains such as behavior, skills, and affective ratings.',
    pathPatterns: [
      /\/assessments(\/|$)/,
      /\/learning-evaluations(\/|$)/,
      /\/result-comments(\/|$)/,
    ],
    whenToUse: [
      'Create assessment components such as CA, exam, project, or mid-term scores.',
      'Configure dependencies where one score should be inserted from an existing result record.',
      'Create evaluation domains and comments used on report sheets.',
    ],
    steps: [
      'Set the assessment name, term, score limit, and applicable classes or subjects.',
      'Confirm the total score structure before teachers begin recording marks.',
      'Use insertion tools only when the source course result is complete and reviewed.',
      'For evaluations, define the domain first, then record student-specific values or comments.',
    ],
    tips: [
      'Assessment changes after result entry can change totals and report interpretation.',
      'Use clear labels because assessment names appear in teacher entry forms and reports.',
    ],
    related: ['Results', 'Course Results', 'Report Sheets'],
  },
  {
    key: 'results',
    title: 'Result Recording, Processing, and Publishing',
    summary:
      'Result pages manage score entry, class result calculations, comments, locking, publishing, report sheets, transcripts, session results, and student result access.',
    pathPatterns: [
      /\/course-result(\/|s\/|-info)/,
      /\/class-result(\/|s\/|-info)/,
      /\/term-results(\/|$)/,
      /\/session-results(\/|$)/,
      /\/result-publications(\/|$)/,
      /\/result-sheets(\/|$)/,
      /\/transcript(\/|$)/,
    ],
    whenToUse: [
      'Record scores for a subject, class, session, and term.',
      'Calculate class results and generate report sheets.',
      'Publish, lock, unlock, print, or review student results.',
    ],
    steps: [
      'Confirm assessment settings before score entry begins.',
      'Record or upload subject scores, then review totals and missing scores.',
      'Calculate course result info and class result info before printing or publishing.',
      'Add teacher/principal comments and evaluations where the result template requires them.',
      'Publish results only after checking grades, positions, averages, comments, and resumption dates.',
    ],
    tips: [
      'Use locking when results are final so accidental edits do not change published reports.',
      'If a student is missing from a result sheet, check class placement, subject mapping, and result calculation status.',
    ],
    related: ['Assessments', 'Subjects', 'PINs', 'Report Sheets'],
  },
  {
    key: 'pins',
    title: 'Result PINs',
    summary:
      'PIN pages generate, display, and manage result access PINs used by students or guardians to activate and view result records.',
    pathPatterns: [/\/pins(\/|$)/, /\/pin-generators(\/|$)/],
    whenToUse: [
      'Generate PIN batches for a result checking workflow.',
      'Display or print PINs for distribution.',
      'Review which students or result records have activated access.',
    ],
    steps: [
      'Choose the correct PIN generation settings and quantity.',
      'Generate the PIN batch and download or print the output securely.',
      'Distribute PINs only to the intended students or guardians.',
      'Use activation records to investigate result access issues.',
    ],
    tips: [
      'Treat PIN files as sensitive because they grant result access.',
      'Confirm the relevant result has been published before users try to activate it.',
    ],
    related: ['Results', 'Students', 'Guardians'],
  },
  {
    key: 'exams',
    title: 'Exams, Events, and CBT',
    summary:
      'Exam pages manage internal exams, public or external exam events, CBT setup, question selection, candidates, leaderboards, and transfer of event results into school records.',
    pathPatterns: [/\/exams(\/|$)/, /\/events(\/|$)/, /\/cbt(\/|$)/],
    whenToUse: [
      'Create CBT exams or external exam events.',
      'Attach subjects, questions, candidates, timing, and access settings.',
      'Transfer completed event scores into result records where supported.',
    ],
    steps: [
      'Create the exam or event with clear dates, timing rules, candidate access, and activation status.',
      'Attach the relevant subjects or courseables and confirm question availability.',
      'Use the offline CBT setup guide when running exams in a local network environment.',
      'Review submitted attempts, leaderboards, or event results before transferring scores.',
    ],
    tips: [
      'Confirm time settings and activation status before candidates begin.',
      'Question banks and subject mappings should be ready before building exams.',
    ],
    related: ['Question Bank', 'Subjects', 'Results', 'Admissions'],
  },
  {
    key: 'admissions',
    title: 'Admissions',
    summary:
      'Admission pages manage admission forms, public application purchase, application review, candidate records, exams, and admission letters.',
    pathPatterns: [
      /\/admissions(\/|$)/,
      /\/admission-(forms|applications)(\/|$)/,
    ],
    whenToUse: [
      'Create admission forms for applicant intake.',
      'Review submitted applications and uploaded documents.',
      'Admit successful applicants or issue admission letters.',
    ],
    steps: [
      'Create the admission form with price, instructions, available classes, and required fields.',
      'Share the public application link or form purchase route with applicants.',
      'Review each application, uploaded documents, and payment status before admission.',
      'Admit the applicant only after confirming class placement and biodata accuracy.',
    ],
    tips: [
      'Admission configuration affects the public applicant experience, so test the form before sharing widely.',
      'Admitting an applicant can create or connect student records depending on the workflow.',
    ],
    related: ['Students', 'Payments', 'Exams', 'Recruitment'],
  },
  {
    key: 'payments',
    title: 'Fees, Payments, Receipts, and Manual Reviews',
    summary:
      'Payment pages cover fee setup, student billing, receipts, payment summaries, manual payment approval, reminders, bank accounts, and reserved account funding.',
    pathPatterns: [
      /\/payments(\/|$)/,
      /\/fees(\/|$)/,
      /\/receipts(\/|$)/,
      /\/manual-payments(\/|$)/,
    ],
    whenToUse: [
      'Create or edit school fees and categories.',
      'Record, review, approve, or reject student fee payments.',
      'Print receipts and summarize fee collection.',
    ],
    steps: [
      'Set up fee titles, amounts, classes, sessions, terms, and categories before payment collection starts.',
      'Use automatic payment records when providers supply verified transactions.',
      'For manual payments, inspect proof, student, amount, fee, reference, and date before approval.',
      'Generate receipts only after the payment record is accurate and assigned to the right student and fee.',
    ],
    tips: [
      'Manual approval changes financial records, so verify amount and payer details carefully.',
      'When a payment is missing, check provider transaction status, manual review status, and the student fee assignment.',
    ],
    related: ['Wallet', 'Bank Accounts', 'Students', 'Receipts'],
  },
  {
    key: 'wallet',
    title: 'Wallets, Funding, Withdrawals, and Bank Accounts',
    summary:
      'Funding and wallet pages manage institution balances, reserved accounts, deposits, transactions, withdrawals, and payout bank details.',
    pathPatterns: [
      /\/fundings(\/|$)/,
      /\/transactions(\/|$)/,
      /\/withdrawals(\/|$)/,
      /\/bank-accounts(\/|$)/,
    ],
    whenToUse: [
      'Fund the institution wallet or review wallet transaction history.',
      'Add bank accounts used for payouts or staff payroll workflows.',
      'Request and track withdrawals.',
    ],
    steps: [
      'Review wallet balance before starting any payment or withdrawal workflow.',
      'Add verified bank account details and confirm the account owner before saving.',
      'Use funding history to trace deposits, provider references, and transaction status.',
      'For withdrawals, confirm bank details, amount, fees, and approval requirements before submitting.',
    ],
    tips: [
      'Bank account mistakes can delay payouts, so review account number and bank name carefully.',
      'Use transaction history when reconciling wallet balance with payment activity.',
    ],
    related: ['Payments', 'Payroll', 'Activity Logs'],
  },
  {
    key: 'payroll',
    title: 'Payroll and Salaries',
    summary:
      'Payroll pages manage salary types, staff salaries, adjustment types, payroll summaries, generated payroll records, and printable payroll details.',
    pathPatterns: [
      /\/payrolls(\/|$)/,
      /\/salaries(\/|$)/,
      /\/salary-types(\/|$)/,
    ],
    whenToUse: [
      'Create salary structures and assign salaries to staff.',
      'Add deductions, bonuses, or other payroll adjustments.',
      'Generate, review, and print payroll records.',
    ],
    steps: [
      'Create salary types and adjustment types before assigning staff salaries.',
      'Assign salaries to staff with accurate amounts and effective details.',
      'Generate payroll summaries for the target period, then review every staff line.',
      'Finalize payroll only after deductions, bonuses, bank details, and totals are correct.',
    ],
    tips: [
      'Payroll depends on correct staff records and bank accounts.',
      'Use summaries for review before creating final payroll outputs.',
    ],
    related: ['Staff', 'Bank Accounts', 'Wallet'],
  },
  {
    key: 'expenses',
    title: 'Expenses',
    summary:
      'Expense pages track school spending, expense categories, supporting details, and exportable expense records for administration and reconciliation.',
    pathPatterns: [/\/expenses(\/|$)/, /\/expense-categories(\/|$)/],
    whenToUse: [
      'Record school expenses by category, amount, date, and description.',
      'Create or update expense categories used for reporting.',
      'Review spending records for a date range or category.',
    ],
    steps: [
      'Create categories that match how the institution reports spending.',
      'When recording expenses, include amount, date, category, payee, and a clear description.',
      'Attach or preserve supporting evidence where the workflow allows it.',
      'Use filters and exports for monthly or termly reconciliation.',
    ],
    tips: [
      'Keep category names stable so reports remain easy to compare across periods.',
      'Expense data should be entered promptly to keep finance reports accurate.',
    ],
    related: ['Wallet', 'Reports', 'Activity Logs'],
  },
  {
    key: 'messages',
    title: 'Messaging, Notifications, and Chats',
    summary:
      'Communication pages send internal notifications, messages, chats, BulkSMS, WhatsApp-related messages, and review sent communication records.',
    pathPatterns: [
      /\/notifications(\/|$)/,
      /\/messages(\/|$)/,
      /\/chats(\/|$)/,
    ],
    whenToUse: [
      'Send announcements or targeted messages to students, guardians, staff, or groups.',
      'Review sent notifications and message delivery history.',
      'Respond to internal chat conversations.',
    ],
    steps: [
      'Choose the right audience before writing the message.',
      'Keep the subject and message body specific enough for recipients to understand the required action.',
      'Preview recipients or filters where available, then send.',
      'Use sent-notification pages and chat status to follow up on unread or unanswered communication.',
    ],
    tips: [
      'Audience mistakes can expose information to the wrong users, so review recipient filters before sending.',
      'Use chats for ongoing conversations and notifications for formal announcements.',
    ],
    related: ['Students', 'Guardians', 'Staff', 'Dashboard Attention Cards'],
  },
  {
    key: 'timetable',
    title: 'Timetables, School Activities, and To-Do',
    summary:
      'Scheduling pages manage class timetables, school activities, to-do items, and live class coordination so academic and administrative work stays organized.',
    pathPatterns: [
      /\/timetables(\/|$)/,
      /\/school-activities(\/|$)/,
      /\/todo-list(\/|$)/,
    ],
    whenToUse: [
      'Create class schedules or update period allocations.',
      'Publish school activities and calendars.',
      'Track administrative tasks with to-do items.',
    ],
    steps: [
      'Select the relevant class, day, session, term, and subject before saving timetable entries.',
      'For school activities, set dates, titles, and descriptions that staff and students can understand.',
      'Use to-do status changes to keep work queues current.',
      'Review schedules after edits to ensure time slots do not conflict.',
    ],
    tips: [
      'Timetable changes affect teachers and students, so make updates before the affected period begins.',
      'Use activities for institution-wide events and timetables for class-specific schedules.',
    ],
    related: ['Subjects', 'Classes', 'Live Classes'],
  },
  {
    key: 'recruitment',
    title: 'Recruitment',
    summary:
      'Recruitment pages manage public vacancy posts, applicant submissions, application review, and recruitment exam entry points where enabled.',
    pathPatterns: [/\/recruitment(\/|$)/, /\/vacancy-posts(\/|$)/],
    whenToUse: [
      'Publish a vacancy for external applicants.',
      'Review submitted recruitment applications.',
      'Open or manage applicant exam workflows.',
    ],
    steps: [
      'Create the vacancy with role, requirements, instructions, deadline, and public visibility.',
      'Share the public vacancy link when the post is ready.',
      'Review applications, documents, and candidate details from the applications list.',
      'Move shortlisted applicants into the next review or exam stage according to the institution process.',
    ],
    tips: [
      'Public vacancies should be checked for spelling, eligibility, and deadline accuracy before publishing.',
      'Keep applicant records organized because they may later become staff records.',
    ],
    related: ['Staff', 'Exams', 'Notifications'],
  },
  {
    key: 'associations',
    title: 'Associations and User Associations',
    summary:
      'Association pages group users into clubs, houses, societies, teams, or other institution-defined associations for tracking and communication.',
    pathPatterns: [/\/associations(\/|$)/, /\/user-associations(\/|$)/],
    whenToUse: [
      'Create association groups used by the institution.',
      'Assign students, staff, or other users to an association.',
      'Review membership lists for activities or communication.',
    ],
    steps: [
      'Create the association with a clear name and description.',
      'Add users from the user-association page and confirm membership details.',
      'Use filters to review members by association or user.',
      'Remove users who are no longer members to keep lists accurate.',
    ],
    tips: [
      'Associations are useful for non-class groupings that do not fit academic class structure.',
      'Clear association names make communication and reporting easier.',
    ],
    related: ['Students', 'Staff', 'Messaging'],
  },
  {
    key: 'settings',
    title: 'Institution Settings and Profile',
    summary:
      'Settings and profile pages control institution identity, result templates, payment keys, academic-session behavior, and other configuration that affects many workflows.',
    pathPatterns: [
      /\/settings(\/|$)/,
      /\/institution-profile(\/|$)/,
      /\/term-details(\/|$)/,
    ],
    whenToUse: [
      'Update school profile information, branding, and operational settings.',
      'Configure result display, payment provider keys, term/session controls, or setup checklist items.',
      'Review term details used across result and academic pages.',
    ],
    steps: [
      'Review the existing setting before changing it because some settings affect published pages.',
      'Update one configuration area at a time, then test the related workflow.',
      'For result settings, check a sample report sheet after saving.',
      'For payment keys, verify provider credentials in a controlled payment test before broad usage.',
    ],
    tips: [
      'Settings can affect many users immediately, so keep changes deliberate and documented.',
      'If a feature behaves unexpectedly, check institution settings before assuming the page is broken.',
    ],
    related: ['Dashboard', 'Results', 'Payments', 'Profile'],
  },
  {
    key: 'reports',
    title: 'Reports, Prints, Imports, and Exports',
    summary:
      'Report and document pages generate printable outputs, downloadable sheets, uploads, CSVs, PDFs, report cards, transcripts, receipts, ID cards, and operational exports.',
    pathPatterns: [
      /\/reports(\/|$)/,
      /\/print(\/|$)/,
      /\/upload(\/|$)/,
      /\/download(\/|$)/,
      /\/report-sheet(\/|$)/,
    ],
    whenToUse: [
      'Print official outputs such as receipts, result sheets, ID cards, reports, or transcripts.',
      'Upload bulk records using supported templates.',
      'Export filtered lists for external review or reconciliation.',
    ],
    steps: [
      'Apply filters first so the report or export contains only the intended records.',
      'Preview printable output before sharing or saving official copies.',
      'For uploads, download the template where available and keep column headers unchanged.',
      'After importing, review the affected list or summary to confirm that records were created correctly.',
    ],
    tips: [
      'Bulk imports can change many records at once; test with a small file when unsure.',
      'Printed outputs reflect current data, so confirm calculations and comments before printing final copies.',
    ],
    related: ['Results', 'Payments', 'Students', 'Staff'],
  },
  {
    key: 'activity-logs',
    title: 'Activity Logs',
    summary:
      'Activity logs show important actions performed in the institution, including who acted, what changed, when it happened, and contextual details for audit review.',
    pathPatterns: [/\/activity-logs(\/|$)/],
    whenToUse: [
      'Investigate sensitive changes such as payment, result, staff, student, or setting updates.',
      'Review actor, subject, category, event, severity, and request context.',
      'Export logs for administrative or compliance review.',
    ],
    steps: [
      'Use date and category filters to narrow the audit list.',
      'Open a row to compare old and new values where the action stored change details.',
      'Check impersonation context when a manager acted through another user account.',
      'Export only the filtered records needed for the review.',
    ],
    tips: [
      'Logs are most useful when filters are specific; broad searches can be noisy.',
      'Financial and result-related actions should be reviewed with the underlying record open for context.',
    ],
    related: ['Payments', 'Results', 'Settings', 'Users'],
  },
];

export function findDashboardExplanation(
  pathname: string,
  uuid: string
): DashboardExplanation | null {
  let institutionPath = new RegExp(`^/${uuid}/[^/]+`);
  if (!institutionPath.test(pathname)) {
    return null;
  }

  return (
    dashboardExplanations.find((explanation) =>
      explanation.pathPatterns.some((pattern) => pattern.test(pathname))
    ) ?? null
  );
}
