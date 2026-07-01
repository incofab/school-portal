import React, { useMemo, useState } from 'react';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  Badge,
  Box,
  Button,
  Collapse,
  Container,
  Flex,
  HStack,
  Heading,
  Icon,
  Input,
  InputGroup,
  InputLeftElement,
  SimpleGrid,
  Stack,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import {
  AcademicCapIcon,
  BanknotesIcon,
  BookOpenIcon,
  BuildingLibraryIcon,
  CalendarDaysIcon,
  ChatBubbleLeftRightIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  ClipboardDocumentCheckIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  UserGroupIcon,
} from '@heroicons/react/24/outline';
import route from '@/util/route';

type KnowledgeBaseItem = {
  id: string;
  category: string;
  title: string;
  keywords: string[];
  answer: string;
  steps?: string[];
  remember?: string[];
};

type SearchResult = KnowledgeBaseItem & {
  score: number;
  snippet: string;
};

const popularSearches = [
  'result',
  'receipt',
  'student',
  'session',
  'teacher',
  'login',
  'payment',
  'attendance',
];

const knowledgeBaseItems: KnowledgeBaseItem[] = [
  {
    id: 'login-main-dashboard',
    category: 'Getting Started and Login',
    title: 'How do I log in and find my dashboard?',
    keywords: ['login', 'dashboard', 'account access', 'school portal', 'home'],
    answer:
      'Open the Edumanager login page and enter your registered email or phone number with your password. After login, Edumanager sends you to the correct dashboard based on your role.\n\nManagers see the manager dashboard. Institution admins, teachers, accountants, guardians, students, and alumni see their school dashboard. Use the left menu to open features such as Students, Classes, Subject, Results, Payments, Attendance, Chats, and Settings.',
  },
  {
    id: 'forgot-password',
    category: 'Getting Started and Login',
    title: 'What should I do if I forgot my password?',
    keywords: ['forgot password', 'reset password', 'login issue', 'password'],
    answer:
      'Use the Forgot Password link on the login page. Enter your registered email or phone number and follow the reset instruction sent by the school or platform.\n\nIf you cannot receive the reset message, contact your school administrator to confirm that your user profile has the correct contact details.',
  },
  {
    id: 'roles-permissions',
    category: 'Getting Started and Login',
    title: 'Why can I not see a feature on my dashboard?',
    keywords: ['permission', 'role', 'access', 'hidden menu', 'admin'],
    answer:
      'Edumanager shows menu items based on your role. An admin can manage most school settings. Teachers usually see subject, class, result, assignment, lesson, attendance, chat, and student-related tools. Accountants see payment, fee, receipt, wallet, bank, and finance tools. Students and guardians see their own results, fees, classes, assignments, chats, and dependents where enabled.\n\nIf a feature is missing, ask an admin to check your role and user association.',
  },
  {
    id: 'switch-institution',
    category: 'Getting Started and Login',
    title: 'How do I switch between schools or institutions?',
    keywords: [
      'switch school',
      'institution',
      'select institution',
      'multi school',
    ],
    answer:
      'If your account belongs to more than one institution, Edumanager shows an institution selection screen after login. Choose the school you want to work in.\n\nIf you expected another school but cannot see it, ask the school admin to add your user account to that institution.',
  },
  {
    id: 'change-password',
    category: 'Getting Started and Login',
    title: 'How do I change my password after logging in?',
    keywords: ['change password', 'security', 'profile', 'account'],
    answer:
      'Open the profile menu in the dashboard header and choose Change Password. Enter your current password, then set the new password.\n\nUse a password that is easy for you to remember but difficult for others to guess. Do not share staff or admin passwords.',
  },
  {
    id: 'setup-current-session-term',
    category: 'Academic Sessions and Terms',
    title: 'How do I set the current academic session and term?',
    keywords: [
      'session',
      'term',
      'current session',
      'current term',
      'settings',
    ],
    answer:
      'Admins should open Settings or Institution Settings and set the current academic session and current term. These settings affect result entry, attendance, dashboards, payments, and report generation.\n\nAlways confirm the current session and term before teachers begin recording scores or accountants begin creating term-based fees.',
  },
  {
    id: 'create-academic-session',
    category: 'Academic Sessions and Terms',
    title: 'How do I create a new academic session?',
    keywords: ['academic session', 'new session', '2024/2025', 'calendar'],
    answer:
      'Open Academic Sessions from the manager or school administration area, then create the new session title, such as 2024/2025. After creating it, set it as the current academic session in institution settings when the school is ready to use it.\n\nDo not delete old sessions that already have results, payments, attendance, or student movement records.',
  },
  {
    id: 'term-details-resumption',
    category: 'Academic Sessions and Terms',
    title: 'Where do I set term details and resumption dates?',
    keywords: ['term details', 'resumption date', 'next term', 'calendar'],
    answer:
      'Open Term Details or Class Result Analysis, depending on your school workflow. Admins or authorized result officers can set resumption dates and term-level details used on report sheets.\n\nConfirm these details before printing or publishing results so report sheets show the correct next-term information.',
  },
  {
    id: 'mid-term-full-term',
    category: 'Academic Sessions and Terms',
    title: 'What is the difference between mid-term and full-term result mode?',
    keywords: ['mid term', 'full term', 'result mode', 'assessment', 'exam'],
    answer:
      'Mid-term mode is for scores recorded before the end-of-term exam. Full-term mode is for final term results that usually include continuous assessment and exam scores.\n\nIf your school uses mid-term results, choose the correct mode before recording, uploading, calculating, or checking results. This prevents scores from entering the wrong result bucket.',
  },
  {
    id: 'add-student',
    category: 'Students, Guardians, and Admissions',
    title: 'How do I add a new student?',
    keywords: [
      'student',
      'add student',
      'register student',
      'admission',
      'profile',
    ],
    answer:
      'Open Students, then choose Add Student. Enter the student bio-data, class, admission number or code, guardian details, contact information, and any required profile fields.\n\nAfter saving, confirm the student appears in the correct class. If the student should log in, share the student login details or ask the admin to confirm the account credentials.',
  },
  {
    id: 'edit-student-profile',
    category: 'Students, Guardians, and Admissions',
    title: 'How do I update a student profile?',
    keywords: ['edit student', 'student profile', 'bio data', 'guardian phone'],
    answer:
      'Open Students, search for the student, then open the student profile or edit page. Update the required details and save.\n\nBe careful when changing class, admission number, guardian phone, or status because those fields can affect result lookup, payments, WhatsApp result checking, and guardian access.',
  },
  {
    id: 'student-class-change',
    category: 'Students, Guardians, and Admissions',
    title: 'How do I move a student to another class?',
    keywords: [
      'class movement',
      'change class',
      'student transfer',
      'promotion',
    ],
    answer:
      'Use Student Class Changes or the student movement feature under Classes or Students. Select the student, the old class, the new class, session, term, and reason for the movement.\n\nUse promotion tools when moving many students at the end of a session. Use individual class movement for corrections, transfers, or special cases.',
  },
  {
    id: 'guardian-dependent',
    category: 'Students, Guardians, and Admissions',
    title: 'How do guardians see their children or dependents?',
    keywords: [
      'guardian',
      'dependents',
      'parent',
      'children',
      'student access',
    ],
    answer:
      'Guardians must have their phone number or user account linked to the student record. After login, guardians can open Dependents to see linked students.\n\nIf a guardian cannot see a child, check that the guardian phone number and user association match the student record.',
  },
  {
    id: 'admission-forms',
    category: 'Students, Guardians, and Admissions',
    title: 'How do admission forms and applications work?',
    keywords: ['admission', 'application', 'admission form', 'applicant'],
    answer:
      'Admins create admission forms from the Admissions menu. Applicants can buy or complete the form through the public admission link when enabled. Staff can review submitted applications, preview details, approve successful applicants, and generate admission letters.\n\nUse admission forms for new intakes instead of manually collecting applicant information outside the platform.',
  },
  {
    id: 'student-status',
    category: 'Students, Guardians, and Admissions',
    title:
      'What should I do when a student graduates, leaves, or is suspended?',
    keywords: ['alumni', 'graduated', 'suspended', 'left school', 'status'],
    answer:
      'Update the student status from the student management area. Use alumni or graduated status for completed students, suspended status for temporary access restriction, and transfer or inactive status where your school process requires it.\n\nAvoid deleting students who already have payments, results, attendance, or receipts. Status changes preserve history.',
  },
  {
    id: 'create-classes',
    category: 'Classes, Subjects, and Teachers',
    title: 'How do I create classes and class divisions?',
    keywords: ['class', 'classes', 'classification', 'class division', 'arm'],
    answer:
      'Open Classes, then create the main class such as JSS 1 or Primary 4. If your school uses arms or divisions such as A, B, Red, or Blue, open Class Divisions and create them under the correct class.\n\nUse class groups when you need broader result grouping, promotion grouping, or reporting across related classes.',
  },
  {
    id: 'create-subjects',
    category: 'Classes, Subjects, and Teachers',
    title: 'How do I create subjects or courses?',
    keywords: ['subject', 'course', 'add subject', 'curriculum'],
    answer:
      'Open Subject, then choose Add Subject. Enter the subject title and save. Admins can create subjects one by one or use bulk tools where available.\n\nAfter creating subjects, assign teachers and map subjects to the classes where they should be taught.',
  },
  {
    id: 'assign-subject-teacher',
    category: 'Classes, Subjects, and Teachers',
    title: 'How do I assign a teacher to a subject?',
    keywords: [
      'teacher',
      'subject teacher',
      'course teacher',
      'assigned subjects',
    ],
    answer:
      'Open Subject Teachers from the Subject menu. Choose the teacher, subject, class, session, and term if required. Save the assignment.\n\nTeachers usually see result entry, assignments, lesson plans, and class tools only for subjects or classes assigned to them.',
  },
  {
    id: 'form-teacher-class-teacher',
    category: 'Classes, Subjects, and Teachers',
    title: 'How do form teachers or class teachers work?',
    keywords: [
      'form teacher',
      'class teacher',
      'teacher',
      'comments',
      'class result',
    ],
    answer:
      'A form teacher is a staff member responsible for a class. Depending on school permissions, form teachers can view class students, record comments, check attendance, review class result analysis, or help with report sheets.\n\nIf a teacher cannot access class result tools, confirm that they are assigned to the class or have the correct role.',
  },
  {
    id: 'scheme-lesson-plan-note',
    category: 'Classes, Subjects, and Teachers',
    title:
      'Where do teachers manage scheme of work, lesson plans, and lesson notes?',
    keywords: [
      'scheme of work',
      'lesson plan',
      'lesson note',
      'curriculum',
      'topics',
    ],
    answer:
      'Teachers and admins can use Scheme of Works, Lesson Plans, Lesson Notes, Topics, and Sub-topics from the academic menus. These tools help structure what should be taught and track lesson preparation.\n\nUse topics and schemes before lesson plans when your school wants organized curriculum coverage by subject, class, term, and week.',
  },
  {
    id: 'record-result',
    category: 'Results and Report Sheets',
    title: 'How do teachers record student results?',
    keywords: [
      'result',
      'record result',
      'scores',
      'assessment',
      'exam',
      'teacher',
    ],
    answer:
      'Open Subject, then choose the relevant result recording page such as Record Course Result, Record Class Course Result, or Record Student Subject Results. Select the subject teacher, class, session, term, and result type.\n\nEnter assessment and exam scores carefully, then save. If mid-term is enabled, choose full-term or mid-term before recording so scores enter the correct result type.',
  },
  {
    id: 'upload-result-sheet',
    category: 'Results and Report Sheets',
    title: 'Can I upload results from Excel?',
    keywords: ['upload result', 'excel', 'bulk result', 'result sheet'],
    answer:
      'Yes. Use the Upload Class Sheet or course result upload tools where available. Download the correct template first, fill scores without changing the required columns, then upload it back.\n\nIf the upload fails, check that student codes, subject mappings, class, session, term, and result type match the template.',
  },
  {
    id: 'calculate-results',
    category: 'Results and Report Sheets',
    title: 'How do I calculate or process results?',
    keywords: [
      'calculate result',
      'process result',
      'class result',
      'positions',
      'grade',
    ],
    answer:
      'After teachers finish recording scores, open Class Result Analysis or Course Result Info and run calculation or processing. This generates aggregates, grades, positions, and term result records.\n\nMake sure all required subject scores are entered before processing. If a score is missing, correct it first or use the school-approved option to calculate with missing scores if available.',
  },
  {
    id: 'missing-result-score',
    category: 'Results and Report Sheets',
    title: 'Why is a student result missing or incomplete?',
    keywords: [
      'missing score',
      'missing result',
      'incomplete result',
      'no result',
    ],
    answer:
      'A result may be missing because scores were not recorded, the wrong session or term was selected, the student was in the wrong class, the result was recorded as mid-term instead of full-term, or the class result has not been processed.\n\nCheck the student class, subject teacher assignment, current session and term, result type, and processing status.',
  },
  {
    id: 'publish-results',
    category: 'Results and Report Sheets',
    title: 'How do I publish results for students and guardians?',
    keywords: [
      'publish result',
      'release result',
      'result publication',
      'guardian',
    ],
    answer:
      'Use Result Publications after results have been checked and approved. Select the session, term, class or student scope, then publish. Published results become available to students and guardians based on your school settings.\n\nDo not publish results until comments, grades, positions, resumption dates, and principal approval are correct.',
  },
  {
    id: 'print-report-sheets',
    category: 'Results and Report Sheets',
    title: 'How do I print or download report sheets?',
    keywords: [
      'report sheet',
      'print result',
      'download result',
      'pdf',
      'signed result',
    ],
    answer:
      'Open the student result sheet, class result sheet, multiple result sheets, transcript, or session result page. Use the print or download button provided on the result page.\n\nIf the layout looks wrong, check the selected result template, class division template, paper size, browser print settings, and whether exam score display is enabled.',
  },
  {
    id: 'result-comments',
    category: 'Results and Report Sheets',
    title: 'Where do teacher and principal comments come from?',
    keywords: [
      'comments',
      'teacher comment',
      'principal comment',
      'result comment',
    ],
    answer:
      'Comments can be typed manually, generated from templates, or added through class result tools depending on your school setup. Result comment templates help schools keep comments consistent.\n\nBefore publishing, review comments for spelling, tone, and student-specific accuracy.',
  },
  {
    id: 'result-pin-activation',
    category: 'Results and Report Sheets',
    title: 'Why is a result asking for an activation PIN?',
    keywords: ['pin', 'activation', 'result checker', 'activate result'],
    answer:
      'Some schools require result activation before students or guardians can view report sheets. Enter the result activation PIN on the result activation page or through the WhatsApp result checker when prompted.\n\nIf the PIN is rejected, confirm that it belongs to the right school group, has not exceeded its usage limit, and matches the student or session rules.',
  },
  {
    id: 'fees-payments',
    category: 'Fees, Payments, and Receipts',
    title: 'How do I create school fees or payment items?',
    keywords: ['fee', 'fees', 'payment item', 'invoice', 'term bill'],
    answer:
      'Accountants or admins can open Fees and create fee items for tuition, admission, transport, hostel, exam, or other charges. Assign the fee to the correct class, session, term, or student group according to your school process.\n\nUse clear fee titles so parents and staff understand what each payment is for.',
  },
  {
    id: 'record-manual-payment',
    category: 'Fees, Payments, and Receipts',
    title: 'How do I record a manual payment?',
    keywords: ['manual payment', 'cash payment', 'bank transfer', 'receipt'],
    answer:
      'Open Manual Payments or Fee Payments, choose the student and fee, enter the amount paid, payment method, reference, and payment date, then save or approve based on your workflow.\n\nPending manual payments should be reviewed before they affect student balances or receipts.',
  },
  {
    id: 'print-receipt',
    category: 'Fees, Payments, and Receipts',
    title: 'How do I print a receipt?',
    keywords: ['receipt', 'print receipt', 'payment receipt', 'invoice'],
    answer:
      'Open Receipts or Fee Payments, search for the student or payment reference, then open the receipt page. Use the browser print option or the available print button.\n\nIf a receipt is missing, confirm that the payment was approved and tied to the correct student and fee.',
  },
  {
    id: 'payment-balance',
    category: 'Fees, Payments, and Receipts',
    title: 'How do I check a student balance?',
    keywords: ['balance', 'debt', 'outstanding payment', 'fee summary'],
    answer:
      'Open Fee Payment Summary, Fee Payments, or the student payment view. Search for the student and review assigned fees, paid amounts, outstanding balances, receipts, and payment history.\n\nIf the balance looks wrong, check duplicate fees, wrong session or term, unapproved manual payments, and payments recorded against the wrong student.',
  },
  {
    id: 'online-payment',
    category: 'Fees, Payments, and Receipts',
    title: 'How do online payments work?',
    keywords: ['online payment', 'paystack', 'payment gateway', 'card payment'],
    answer:
      'When payment gateway keys are configured, students or guardians can pay online from the payment page. Edumanager records successful payment callbacks and links them to the relevant payment reference.\n\nIf online payments are not working, check payment key settings, callback configuration, school bank setup, and the payment provider dashboard.',
  },
  {
    id: 'wallet-funding',
    category: 'Wallets, Commissions, and Withdrawals',
    title: 'What is the wallet used for?',
    keywords: ['wallet', 'funding', 'transactions', 'credit', 'debit'],
    answer:
      'Institution and group wallets track platform credits, service charges, funding, deductions, and related transactions. Managers and authorized admins can view wallet activity from Funding or Transactions.\n\nKeep wallet funding records clear so billing, message charges, result publication charges, or service deductions can be audited.',
  },
  {
    id: 'withdrawals-bank-accounts',
    category: 'Wallets, Commissions, and Withdrawals',
    title: 'How do bank accounts and withdrawals work?',
    keywords: [
      'withdrawal',
      'bank account',
      'settlement',
      'account verification',
    ],
    answer:
      'Add verified bank accounts before requesting withdrawals. Authorized users can create withdrawal requests, and managers can review or update withdrawal status.\n\nIf verification fails, confirm the bank, account number, account name, and any required BVN or NIN details.',
  },
  {
    id: 'partner-commissions',
    category: 'Wallets, Commissions, and Withdrawals',
    title: 'Where can partners or managers view commissions?',
    keywords: ['commission', 'partner', 'earnings', 'funding'],
    answer:
      'Managers and partners can open Commissions from the manager dashboard. Commission records usually relate to institution registration, subscription, funding, or platform business rules.\n\nIf a commission is missing, check whether the linked transaction has been completed and whether the partner relationship is correctly recorded.',
  },
  {
    id: 'take-attendance',
    category: 'Attendance, Timetable, and Activities',
    title: 'How do I take attendance?',
    keywords: ['attendance', 'check in', 'absent', 'present', 'register'],
    answer:
      'Open Attendance and choose the class, date, session, and term. Mark students as present, absent, late, or the status your school uses, then save.\n\nUse the class attendance register or attendance reports to review patterns and follow up on frequent absences.',
  },
  {
    id: 'attendance-report',
    category: 'Attendance, Timetable, and Activities',
    title: 'How do I view attendance reports?',
    keywords: ['attendance report', 'student attendance', 'class register'],
    answer:
      'Open Attendance Reports or Student Attendance Report. Select the class, student, date range, session, and term as needed.\n\nReports help form teachers, admins, and school owners track punctuality, absences, and class attendance trends.',
  },
  {
    id: 'timetable-activities',
    category: 'Attendance, Timetable, and Activities',
    title: 'Where do I manage timetables and school activities?',
    keywords: ['timetable', 'school activity', 'calendar', 'event'],
    answer:
      'Use Timetables for class or school schedules. Use School Activities for events, programs, reminders, and school-wide activities.\n\nKeep schedules current so teachers and students can plan lessons, assessments, live classes, and school events.',
  },
  {
    id: 'todo-live-class',
    category: 'Attendance, Timetable, and Activities',
    title: 'How do to-do items and live classes help staff and students?',
    keywords: ['todo', 'tasks', 'live class', 'online class'],
    answer:
      'To-do items help staff track internal tasks. Live Classes help schools organize online class sessions where enabled.\n\nTeachers should add clear titles, dates, class or subject information, and links or instructions so students know what to join or complete.',
  },
  {
    id: 'send-message',
    category: 'Communication and Notifications',
    title: 'How do I send messages or notifications?',
    keywords: ['message', 'notification', 'sms', 'whatsapp', 'email'],
    answer:
      'Open Messages or Notifications, choose the target audience, write the message, and send. Depending on setup, Edumanager can support internal notifications, SMS, WhatsApp, and other channels.\n\nUse clear message titles and avoid sending sensitive student or payment information to the wrong audience.',
  },
  {
    id: 'chat-users',
    category: 'Communication and Notifications',
    title: 'How do chats work inside Edumanager?',
    keywords: ['chat', 'inbox', 'unread', 'conversation', 'help desk'],
    answer:
      'Open Chats from the dashboard. You can search conversations, start a new chat, open existing threads, and reply to messages. Unread counts help you know which conversations need attention.\n\nSchool staff can use chats for guardian support, student questions, staff coordination, and internal follow-up.',
  },
  {
    id: 'whatsapp-result-checker',
    category: 'Communication and Notifications',
    title: 'How does WhatsApp result checking work?',
    keywords: ['whatsapp', 'check result', 'result link', 'guardian phone'],
    answer:
      'When WhatsApp integration is configured, guardians can message the school WhatsApp number to check results. Edumanager matches the sender phone number to linked students, asks the user to choose a student if needed, checks publication and activation status, then sends a signed result link.\n\nIf the student is not found, confirm the guardian phone number on the student profile.',
  },
  {
    id: 'bulk-sms',
    category: 'Communication and Notifications',
    title: 'What should I check before sending bulk SMS or WhatsApp messages?',
    keywords: ['bulk sms', 'whatsapp', 'message charges', 'delivery'],
    answer:
      'Confirm the recipient list, message content, sender balance or wallet, and channel settings. Send short, clear messages and avoid unnecessary repeats.\n\nIf messages fail, check phone number format, wallet balance, provider credentials, and whether the message job has been processed.',
  },
  {
    id: 'assignments',
    category: 'Assignments, Exams, and CBT',
    title: 'How do teachers create assignments?',
    keywords: ['assignment', 'homework', 'submission', 'teacher'],
    answer:
      'Open Assignments and choose Create Assignment. Select the class or students, subject, due date, instructions, and any attachment if supported. Students can view the assignment and submit work according to your school workflow.\n\nTeachers can review submissions from Assignment Submissions.',
  },
  {
    id: 'exam-cbt',
    category: 'Assignments, Exams, and CBT',
    title: 'How do CBT exams work?',
    keywords: ['exam', 'cbt', 'objective', 'theory', 'questions', 'event'],
    answer:
      'Admins or authorized staff create exams or events, add subjects, configure questions, set schedules, and publish or start the exam when ready. Students log in through the exam page and answer objective or theory questions based on the exam setup.\n\nAfter the exam, staff can view results, transfer scores, and evaluate theory answers where required.',
  },
  {
    id: 'question-bank',
    category: 'Assignments, Exams, and CBT',
    title: 'Where do I manage questions and practice content?',
    keywords: ['question bank', 'ccd', 'practice', 'questions', 'topics'],
    answer:
      'Use question bank, CCD, topics, and practice question tools where enabled. Organize questions by subject, topic, class, and type so they can be reused in exams or student practice.\n\nGood question organization makes CBT setup faster and improves practice tracking.',
  },
  {
    id: 'transfer-exam-result',
    category: 'Assignments, Exams, and CBT',
    title: 'Can exam scores be transferred into term results?',
    keywords: [
      'transfer result',
      'exam result',
      'course result',
      'event result',
    ],
    answer:
      'Yes, authorized users can transfer matching exam or event scores into course or term result records when the exam setup and student mappings are correct.\n\nBefore transfer, confirm student codes, subject mappings, class, session, term, and result type. After transfer, recalculate result summaries if required.',
  },
  {
    id: 'payroll',
    category: 'Payroll, Expenses, and Administration',
    title: 'How do payroll and salaries work?',
    keywords: ['payroll', 'salary', 'staff salary', 'adjustment', 'allowance'],
    answer:
      'Admins can create salary types, salary records, payroll adjustments, and payroll summaries. Use salary components for allowances, deductions, bonuses, or recurring staff payment structure.\n\nReview payroll carefully before approval because it affects financial reports and staff payment history.',
  },
  {
    id: 'expenses',
    category: 'Payroll, Expenses, and Administration',
    title: 'How do I record school expenses?',
    keywords: ['expense', 'expense category', 'spending', 'finance'],
    answer:
      'Open Expenses, create expense categories if needed, then record each expense with date, amount, category, description, and supporting details.\n\nUse consistent categories so school owners can understand spending patterns and compare expenses over time.',
  },
  {
    id: 'activity-logs',
    category: 'Payroll, Expenses, and Administration',
    title: 'Can admins see important changes made in the system?',
    keywords: ['activity log', 'audit', 'security', 'changes', 'history'],
    answer:
      'Yes. Activity Logs show important actions such as result changes, payments, publications, PIN usage, messages, and other administrative events where logging is enabled.\n\nUse audit logs to investigate disputes, corrections, unauthorized changes, and operational history.',
  },
  {
    id: 'bank-bvn-nin',
    category: 'Payroll, Expenses, and Administration',
    title: 'Why does Edumanager ask for BVN, NIN, or bank details?',
    keywords: [
      'bvn',
      'nin',
      'bank details',
      'verification',
      'reserved account',
    ],
    answer:
      'Some payment, wallet, reserved account, withdrawal, or verification workflows require valid identity or bank information. Only authorized users should update these details.\n\nIf account verification fails, confirm the exact name, bank, account number, BVN, NIN, and provider requirements.',
  },
  {
    id: 'imports-exports',
    category: 'Imports, Exports, and Printing',
    title: 'What can I import or export?',
    keywords: ['import', 'export', 'excel', 'download', 'upload'],
    answer:
      'Edumanager supports several imports and exports such as students, staff, result sheets, pins, class lists, payment records, reports, and printable outputs depending on the module.\n\nAlways download the correct template before uploading. Keep column names and student codes unchanged unless the template instructs otherwise.',
  },
  {
    id: 'print-id-card',
    category: 'Imports, Exports, and Printing',
    title: 'How do I print student or staff ID cards?',
    keywords: ['id card', 'student card', 'staff card', 'print'],
    answer:
      'Open Students or Staff, then choose the ID card option if enabled. Select the class, staff group, or students to print.\n\nCheck names, photos, class, codes, and school logo before printing in bulk.',
  },
  {
    id: 'transcripts',
    category: 'Imports, Exports, and Printing',
    title: 'How do I generate student transcripts?',
    keywords: ['transcript', 'cumulative result', 'session result', 'history'],
    answer:
      'Open the transcript or cumulative result area for the student. Select the relevant sessions and classes, then generate or print the transcript.\n\nTranscripts depend on historical results, so make sure previous term and session results were correctly processed.',
  },
  {
    id: 'documents-files',
    category: 'Imports, Exports, and Printing',
    title: 'What should I do when uploaded files or documents do not show?',
    keywords: ['file upload', 'document', 'image', 'photo', 'attachment'],
    answer:
      'Check the file size, file type, internet connection, and whether the save action completed successfully. For images, use clear JPG or PNG files where possible.\n\nIf a file was uploaded but not visible, refresh the page and confirm the record was saved to the correct student, staff, assignment, lesson note, or application.',
  },
  {
    id: 'result-settings',
    category: 'Settings and Troubleshooting',
    title: 'Where do I change result templates and result display settings?',
    keywords: [
      'result settings',
      'template',
      'exam display',
      'positions',
      'report sheet',
    ],
    answer:
      'Open Result Settings from Institution Settings. Admins can choose the default result template, whether positions should display, how exam scores appear, and other report sheet behavior.\n\nPreview the result template before publishing or printing many reports.',
  },
  {
    id: 'payment-settings',
    category: 'Settings and Troubleshooting',
    title: 'Where do I configure payment keys and payment settings?',
    keywords: [
      'payment keys',
      'paystack',
      'monnify',
      'payment settings',
      'gateway',
    ],
    answer:
      'Open Payment Keys or Payment Settings. Add the required public and private keys for the payment provider your school uses.\n\nOnly trusted admins should manage payment keys. Incorrect keys can cause online payments or callbacks to fail.',
  },
  {
    id: 'current-session-wrong',
    category: 'Settings and Troubleshooting',
    title: 'Why are records showing under the wrong session or term?',
    keywords: ['wrong session', 'wrong term', 'current session', 'settings'],
    answer:
      'Most academic records depend on the selected or current session and term. If records appear in the wrong place, check institution settings and the filters on the page you are using.\n\nCorrect the current session and term before creating new results, attendance, fees, assignments, or class records.',
  },
  {
    id: 'search-filter-pages',
    category: 'Settings and Troubleshooting',
    title: 'How do I use search and filters on list pages?',
    keywords: ['search', 'filter', 'table', 'list', 'find record'],
    answer:
      'Most list pages include search, filters, pagination, and action buttons. Search by name, code, title, reference, or other visible values. Use filters for class, session, term, status, date, or role where available.\n\nIf you cannot find a record, clear filters and search with fewer words.',
  },
  {
    id: 'support-next-step',
    category: 'Settings and Troubleshooting',
    title: 'What should I send support when I still need help?',
    keywords: ['support', 'help', 'problem', 'issue', 'contact'],
    answer:
      'Send the school name, your role, the page you were using, what you expected, what happened, the student or payment reference if relevant, and a screenshot.\n\nFor result, payment, admission, or payroll issues, include the session, term, class, student name, and exact action you were trying to complete.',
  },
];

const categoryIcons: Record<string, React.ElementType> = {
  'Getting Started and Login': ShieldCheckIcon,
  'Academic Sessions and Terms': CalendarDaysIcon,
  'Students, Guardians, and Admissions': UserGroupIcon,
  'Classes, Subjects, and Teachers': AcademicCapIcon,
  'Results and Report Sheets': ClipboardDocumentCheckIcon,
  'Fees, Payments, and Receipts': BanknotesIcon,
  'Wallets, Commissions, and Withdrawals': BuildingLibraryIcon,
  'Attendance, Timetable, and Activities': CalendarDaysIcon,
  'Communication and Notifications': ChatBubbleLeftRightIcon,
  'Assignments, Exams, and CBT': BookOpenIcon,
  'Payroll, Expenses, and Administration': DocumentTextIcon,
  'Imports, Exports, and Printing': DocumentTextIcon,
  'Settings and Troubleshooting': ShieldCheckIcon,
};

const categoryGuides: Record<
  string,
  {
    who: string;
    where: string;
    before: string[];
    steps: string[];
    troubleshooting: string[];
  }
> = {
  'Getting Started and Login': {
    who: 'All users: school owners, admins, teachers, accountants, students, guardians, alumni, and managers.',
    where:
      'Start from the public login page. After signing in, use the dashboard header and the left sidebar menu to move around.',
    before: [
      'Confirm that you are using the correct login page for your account type.',
      'Use the phone number, email, or credentials your school registered for you.',
      'If you belong to more than one school, choose the correct institution after login.',
    ],
    steps: [
      'Open the login page and enter your registered details.',
      'Wait for Edumanager to redirect you to the dashboard that matches your role.',
      'Use the left menu to find major areas such as Students, Subject, Classes, Payments, Results, Attendance, Chats, and Settings.',
      'If you cannot find a page, check whether your role is allowed to use that feature.',
    ],
    troubleshooting: [
      'If login fails, check spelling, phone number format, and password first.',
      'If a menu is missing, ask an admin to review your role and user association.',
      'If the wrong school opens, return to institution selection or ask support to check your linked institutions.',
    ],
  },
  'Academic Sessions and Terms': {
    who: 'Admins, school owners, result officers, academic officers, and managers who configure school calendars.',
    where:
      'Use Academic Sessions, Institution Settings, Term Details, and result-related settings from the dashboard menu.',
    before: [
      'Decide the session title your school uses, for example 2024/2025.',
      'Confirm the active term before staff begin recording attendance, fees, assignments, or results.',
      'Check whether your school uses mid-term results, full-term results, or both.',
    ],
    steps: [
      'Create the academic session if it does not already exist.',
      'Set the current session and current term in institution settings.',
      'Set term details such as resumption date and result display rules where needed.',
      'Ask staff to refresh their dashboard or reselect filters after changing session or term settings.',
    ],
    troubleshooting: [
      'If records appear missing, check whether the page filter is using a different session or term.',
      'If results appear under mid-term instead of full-term, confirm the selected result mode.',
      'If report sheets show the wrong resumption date, update Term Details before printing.',
    ],
  },
  'Students, Guardians, and Admissions': {
    who: 'Admins, registrars, admission officers, form teachers, guardians, and student-support staff.',
    where:
      'Use Students, Guardians, Admissions, Student Class Changes, Dependents, and student profile pages.',
    before: [
      'Prepare the student name, admission number or code, class, guardian details, phone number, and status.',
      'Confirm the correct class and class division before saving.',
      'Avoid deleting students who already have results, payments, attendance, or receipts.',
    ],
    steps: [
      'Open the relevant Students, Guardians, or Admissions page.',
      'Search first to avoid creating a duplicate record.',
      'Create or update the student record with complete contact and class information.',
      'Link guardian details so parents can access dependents and receive communication where enabled.',
    ],
    troubleshooting: [
      'If a guardian cannot see a child, check the guardian phone number and linked user account.',
      'If a student result or payment is missing, confirm the student was in the correct class for that session and term.',
      'If a student should no longer access the portal, change status instead of deleting historical records.',
    ],
  },
  'Classes, Subjects, and Teachers': {
    who: 'Admins, academic officers, heads of school, form teachers, and subject teachers.',
    where:
      'Use Classes, Class Divisions, Class Groups, Subject, Subject Teachers, Topics, Scheme of Work, Lesson Plans, and Lesson Notes.',
    before: [
      'Create classes before assigning students, teachers, subjects, or results.',
      'Create subjects before assigning subject teachers.',
      'Confirm whether the teacher should access one subject, one class, or several classes.',
    ],
    steps: [
      'Create the class and class division structure your school uses.',
      'Create the subjects or courses taught in the school.',
      'Assign subject teachers to the correct class, subject, session, and term.',
      'Use curriculum tools such as topics, schemes, lesson plans, and lesson notes to organize teaching work.',
    ],
    troubleshooting: [
      'If a teacher cannot record results, confirm the subject teacher assignment.',
      'If a class is missing in filters, check that it was created under the right institution.',
      'If a student appears in the wrong class result, review student class movement history.',
    ],
  },
  'Results and Report Sheets': {
    who: 'Teachers, form teachers, result officers, principals, admins, students, and guardians.',
    where:
      'Use Subject result pages, Recorded Results, Class Result, Result Publications, Result Settings, Result Sheets, Transcripts, and Result PIN pages.',
    before: [
      'Confirm current session, term, class, subject, and result type before entering scores.',
      'Check that subject teachers and class mappings are correct.',
      'Review scores, grades, comments, positions, and resumption dates before publishing.',
    ],
    steps: [
      'Teachers record or upload scores for the correct subject, class, session, term, and result mode.',
      'Result officers calculate or process class results after scores are complete.',
      'Admins or authorized staff review report sheets and comments.',
      'Publish results only after approval, then students and guardians can view them based on school settings.',
    ],
    troubleshooting: [
      'If a result is missing, check scores, result mode, processing status, publication status, and student class history.',
      'If a report sheet is blocked, check publication and result activation settings.',
      'If positions or grades look wrong, recalculate after correcting scores and class membership.',
    ],
  },
  'Fees, Payments, and Receipts': {
    who: 'Accountants, bursars, admins, school owners, students, and guardians.',
    where:
      'Use Fees, Fee Payments, Manual Payments, Payment Summary, Receipts, Payment Keys, and student payment pages.',
    before: [
      'Confirm the correct student, class, session, term, fee item, amount, and payment reference.',
      'Review pending manual payments before issuing or relying on receipts.',
      'Check online payment settings before asking parents to pay through the portal.',
    ],
    steps: [
      'Create fee items with clear names and correct scope.',
      'Record or approve payments against the right student and fee.',
      'Open payment summaries to confirm paid amount and outstanding balance.',
      'Print or share receipts only after the payment is saved and approved.',
    ],
    troubleshooting: [
      'If a receipt is missing, confirm that the payment was approved.',
      'If a balance looks wrong, check duplicate fees, wrong term, wrong student, or unapproved payment.',
      'If online payment fails, check provider keys, callback settings, and payment reference status.',
    ],
  },
  'Wallets, Commissions, and Withdrawals': {
    who: 'School owners, managers, partners, finance admins, and platform finance staff.',
    where:
      'Use Wallet, Funding, Transactions, Commissions, Bank Accounts, and Withdrawals from the finance or manager menus.',
    before: [
      'Confirm the institution group, amount, reference, and reason before funding or deducting.',
      'Verify bank account details before requesting withdrawals.',
      'Review transaction history when investigating balances or commissions.',
    ],
    steps: [
      'Open the relevant wallet, funding, commission, or withdrawal page.',
      'Search by institution, reference, date, or status.',
      'Record or review the transaction carefully.',
      'Keep notes clear enough for finance audit and later support review.',
    ],
    troubleshooting: [
      'If a balance is not expected, compare funding, deduction, charge, and withdrawal records.',
      'If withdrawal fails, check bank account verification and status.',
      'If commission is missing, confirm the linked institution or transaction was completed.',
    ],
  },
  'Attendance, Timetable, and Activities': {
    who: 'Teachers, form teachers, admins, students, and school operations staff.',
    where:
      'Use Attendance, Attendance Reports, Timetables, School Activities, To-do, and Live Classes.',
    before: [
      'Select the correct class, date, session, and term.',
      'Confirm whether attendance is daily, class-based, subject-based, or event-based for your school.',
      'Use clear titles and dates for activities and live classes.',
    ],
    steps: [
      'Open the attendance or scheduling page from the dashboard menu.',
      'Choose the class, date, and required filters.',
      'Mark attendance or create the schedule item.',
      'Review reports to spot absences, lateness, or upcoming school activities.',
    ],
    troubleshooting: [
      'If attendance does not show, check the date range, class, session, and term filters.',
      'If students are missing, confirm they are in the selected class.',
      'If a live class link is wrong, edit the live class before students join.',
    ],
  },
  'Communication and Notifications': {
    who: 'Admins, teachers, accountants, guardians, students, managers, and support staff.',
    where:
      'Use Chats, Messages, Notifications, WhatsApp result checking, Bulk SMS, and sent notification pages.',
    before: [
      'Confirm the audience before sending any message.',
      'Check phone numbers and contact details on student, guardian, and staff profiles.',
      'Avoid sending private student, result, or payment information to the wrong group.',
    ],
    steps: [
      'Open the communication page for the channel you want to use.',
      'Choose the target users or group.',
      'Write a short and clear message.',
      'Send, then monitor sent messages, unread chats, or delivery status where available.',
    ],
    troubleshooting: [
      'If WhatsApp result checking cannot find a student, check the guardian phone on the student profile.',
      'If bulk messages fail, check phone format, wallet balance, provider settings, and queued jobs.',
      'If users do not receive internal notifications, confirm they are active users in the institution.',
    ],
  },
  'Assignments, Exams, and CBT': {
    who: 'Teachers, exam officers, admins, students, and academic coordinators.',
    where:
      'Use Assignments, Assignment Submissions, Exams, Events, CBT pages, Question Bank, and Result Transfer tools.',
    before: [
      'Confirm the class, subject, teacher, dates, instructions, and students involved.',
      'Prepare questions and scoring rules before starting CBT exams.',
      'Check student mappings before transferring exam scores into term results.',
    ],
    steps: [
      'Create the assignment, exam, or event with clear instructions.',
      'Attach or enter questions and choose the correct participants.',
      'Monitor submissions or exam attempts.',
      'Review scores and transfer results only after confirming mappings and completion.',
    ],
    troubleshooting: [
      'If students cannot see an assignment or exam, confirm class and schedule settings.',
      'If scores do not transfer, check student codes, subject mapping, session, term, and result type.',
      'If theory answers need marking, use the theory evaluation page before finalizing results.',
    ],
  },
  'Payroll, Expenses, and Administration': {
    who: 'School owners, admins, accountants, payroll officers, and managers.',
    where:
      'Use Payroll, Salary Types, Salaries, Expenses, Expense Categories, Activity Logs, Bank Accounts, and user verification tools.',
    before: [
      'Confirm staff details, salary components, expense category, amount, and date.',
      'Use consistent expense categories for useful financial reports.',
      'Review sensitive changes in activity logs when investigating disputes.',
    ],
    steps: [
      'Open the payroll, expense, or administration page.',
      'Create the required category or component if it does not exist.',
      'Record the salary, adjustment, expense, or admin action.',
      'Review summaries and logs before final approval or reporting.',
    ],
    troubleshooting: [
      'If payroll totals look wrong, check salary components and adjustments.',
      'If expenses are hard to analyze, clean up category names and date filters.',
      'If an admin action is disputed, check activity logs for who changed what and when.',
    ],
  },
  'Imports, Exports, and Printing': {
    who: 'Admins, teachers, accountants, result officers, registrars, and operations staff.',
    where:
      'Use download, upload, print, export, result sheet, transcript, ID card, receipt, and report pages.',
    before: [
      'Download the official template before uploading Excel data.',
      'Do not rename required columns or change student codes in templates.',
      'Preview printable documents before printing in bulk.',
    ],
    steps: [
      'Open the page for the record you want to import, export, or print.',
      'Apply the correct filters such as class, session, term, date, or status.',
      'Download, upload, preview, or print using the page action button.',
      'Check the output for missing names, wrong class, wrong amount, or wrong session before sharing.',
    ],
    troubleshooting: [
      'If upload fails, check file format, required columns, student codes, and class/session filters.',
      'If print layout looks wrong, check browser print settings and paper size.',
      'If exported data is missing records, clear filters and try again.',
    ],
  },
  'Settings and Troubleshooting': {
    who: 'Admins, school owners, managers, result officers, accountants, and support staff.',
    where:
      'Use Institution Settings, Result Settings, Payment Keys, user roles, filters, setup checklist, and relevant module settings.',
    before: [
      'Understand what the setting affects before changing it.',
      'Tell staff when changing current session, term, result mode, payment keys, or templates.',
      'Take note of the page, filters, and user role when troubleshooting.',
    ],
    steps: [
      'Start by checking the page filters and current session or term.',
      'Confirm the user role and whether the record exists in the correct institution.',
      'Review settings that control the feature.',
      'If support is needed, send school name, role, page, screenshot, and exact action attempted.',
    ],
    troubleshooting: [
      'Most missing-record issues are caused by wrong filters, wrong session, wrong term, or wrong class.',
      'Most access issues are caused by role permissions or missing user association.',
      'Most payment/result issues need the student name, class, session, term, and reference to investigate quickly.',
    ],
  },
};

const normalize = (value: string) =>
  value
    .toLowerCase()
    .replace(/[^a-z0-9/ ]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

const buildSnippet = (item: KnowledgeBaseItem, query: string) => {
  const normalizedQuery = normalize(query);
  if (!normalizedQuery) {
    return item.answer.slice(0, 140);
  }

  const answer = item.answer.replace(/\s+/g, ' ');
  const answerIndex = normalize(answer).indexOf(normalizedQuery);
  if (answerIndex < 0) {
    return answer.slice(0, 160);
  }

  const start = Math.max(0, answerIndex - 55);
  const end = Math.min(
    answer.length,
    answerIndex + normalizedQuery.length + 110
  );

  return `${start > 0 ? '...' : ''}${answer.slice(start, end)}${
    end < answer.length ? '...' : ''
  }`;
};

const scoreItem = (item: KnowledgeBaseItem, query: string) => {
  const normalizedQuery = normalize(query);
  if (!normalizedQuery) {
    return 1;
  }

  const title = normalize(item.title);
  const category = normalize(item.category);
  const keywords = item.keywords.map(normalize);
  const answer = normalize(item.answer);
  const tokens = normalizedQuery.split(' ').filter(Boolean);
  let score = 0;

  if (title.includes(normalizedQuery)) {
    score += 90;
  }
  if (title.startsWith(normalizedQuery)) {
    score += 25;
  }
  if (category.includes(normalizedQuery)) {
    score += 45;
  }
  if (keywords.some((keyword) => keyword.includes(normalizedQuery))) {
    score += 60;
  }
  if (answer.includes(normalizedQuery)) {
    score += 15;
  }

  tokens.forEach((token) => {
    if (title.includes(token)) {
      score += 18;
    }
    if (category.includes(token)) {
      score += 10;
    }
    if (keywords.some((keyword) => keyword.includes(token))) {
      score += 14;
    }
    if (answer.includes(token)) {
      score += 4;
    }
  });

  return score;
};

const guidanceFor = (item: KnowledgeBaseItem) =>
  categoryGuides[item.category] ??
  categoryGuides['Settings and Troubleshooting'];

const DetailList = ({ title, items }: { title: string; items: string[] }) => {
  if (items.length === 0) {
    return null;
  }

  return (
    <Box>
      <Text fontWeight="bold" mb={2}>
        {title}
      </Text>
      <Stack spacing={2} as="ol" pl={5}>
        {items.map((item) => (
          <Text key={item} as="li" color="inherit" lineHeight="1.7">
            {item}
          </Text>
        ))}
      </Stack>
    </Box>
  );
};

export default function KnowledgeBasePage() {
  const [query, setQuery] = useState('');
  const [openIds, setOpenIds] = useState<string[]>([
    knowledgeBaseItems[0]?.id ?? '',
  ]);
  const [focusedId, setFocusedId] = useState<string | null>(null);

  const pageBg = useColorModeValue('gray.50', 'gray.900');
  const panelBg = useColorModeValue('white', 'gray.800');
  const mutedText = useColorModeValue('gray.600', 'gray.300');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const softBg = useColorModeValue('brand.50', 'gray.700');
  const subtleCardBg = useColorModeValue('gray.50', 'gray.900');
  const warningBg = useColorModeValue('orange.50', 'rgba(251, 191, 36, 0.08)');

  const categories = useMemo(
    () =>
      Array.from(new Set(knowledgeBaseItems.map((item) => item.category))).map(
        (category) => ({
          title: category,
          items: knowledgeBaseItems.filter(
            (item) => item.category === category
          ),
        })
      ),
    []
  );

  const searchResults = useMemo<SearchResult[]>(() => {
    const normalizedQuery = normalize(query);
    if (!normalizedQuery) {
      return [];
    }

    return knowledgeBaseItems
      .map((item) => ({
        ...item,
        score: scoreItem(item, normalizedQuery),
        snippet: buildSnippet(item, normalizedQuery),
      }))
      .filter((item) => item.score > 0)
      .sort(
        (first, second) =>
          second.score - first.score || first.title.localeCompare(second.title)
      )
      .slice(0, 12);
  }, [query]);

  const toggleItem = (id: string) => {
    setOpenIds((current) =>
      current.includes(id)
        ? current.filter((itemId) => itemId !== id)
        : [...current, id]
    );
  };

  const openAndScrollToItem = (id: string) => {
    setOpenIds((current) =>
      current.includes(id) ? current : [...current, id]
    );
    setFocusedId(id);
    window.setTimeout(() => {
      document.getElementById(id)?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      });
    }, 50);
  };

  const scrollToCategory = (category: string) => {
    document
      .getElementById(`category-${normalize(category).replaceAll(' ', '-')}`)
      ?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      });
  };

  return (
    <Box minH="100vh" bg={pageBg}>
      <Box bg={panelBg} borderBottomWidth={1} borderColor={borderColor}>
        <Container maxW="7xl" py={4}>
          <Flex
            align={{ base: 'stretch', md: 'center' }}
            justify="space-between"
            gap={3}
            direction={{ base: 'column', md: 'row' }}
          >
            <HStack spacing={3}>
              <Box
                boxSize={10}
                rounded="md"
                bg="brand.600"
                color="white"
                display="grid"
                placeItems="center"
                fontWeight="bold"
              >
                EM
              </Box>
              <Box>
                <Text fontWeight="bold" color="brand.700">
                  EduManager
                </Text>
                <Text fontSize="sm" color={mutedText}>
                  Knowledge Base
                </Text>
              </Box>
            </HStack>
            <HStack spacing={3}>
              <Button
                as={InertiaLink}
                href={route('login')}
                size="sm"
                variant="outline"
                colorScheme="brand"
              >
                Login
              </Button>
              <Button
                as={InertiaLink}
                href={route('home')}
                size="sm"
                colorScheme="brand"
              >
                Home
              </Button>
            </HStack>
          </Flex>
        </Container>
      </Box>

      <Container maxW="7xl" py={{ base: 8, md: 12 }}>
        <Stack spacing={8}>
          <Box
            bg={panelBg}
            borderWidth={1}
            borderColor={borderColor}
            rounded="lg"
            p={{ base: 5, md: 8 }}
          >
            <Stack spacing={6}>
              <Stack spacing={3} maxW="4xl">
                <Badge alignSelf="flex-start" colorScheme="brand" rounded="md">
                  Help Center
                </Badge>
                <Heading size={{ base: 'xl', md: '2xl' }}>
                  Edumanager Knowledge Base
                </Heading>
                <Text color={mutedText} fontSize={{ base: 'md', md: 'lg' }}>
                  Find quick answers about students, results, payments, classes,
                  staff, report sheets, attendance, messages, admissions,
                  payroll, exams, and school administration.
                </Text>
                <Text color={mutedText}>
                  Each article explains who normally uses the feature, where to
                  find it in the dashboard, what to confirm before starting, the
                  steps to follow, and what to check when something does not
                  look right.
                </Text>
              </Stack>

              <InputGroup size="lg">
                <InputLeftElement pointerEvents="none">
                  <Icon as={MagnifyingGlassIcon} color="gray.400" boxSize={5} />
                </InputLeftElement>
                <Input
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  placeholder="Search result, receipt, student, session, teacher, login..."
                  bg={useColorModeValue('white', 'gray.900')}
                  borderColor={borderColor}
                />
              </InputGroup>

              <HStack spacing={2} flexWrap="wrap">
                <Text fontSize="sm" color={mutedText} mr={1}>
                  Popular:
                </Text>
                {popularSearches.map((search) => (
                  <Button
                    key={search}
                    size="xs"
                    variant="outline"
                    colorScheme="brand"
                    onClick={() => setQuery(search)}
                  >
                    {search}
                  </Button>
                ))}
              </HStack>
            </Stack>
          </Box>

          {query.trim() && (
            <Box
              bg={panelBg}
              borderWidth={1}
              borderColor={borderColor}
              rounded="lg"
              p={{ base: 4, md: 5 }}
            >
              <Flex
                align={{ base: 'stretch', md: 'center' }}
                justify="space-between"
                gap={3}
                mb={4}
                direction={{ base: 'column', md: 'row' }}
              >
                <Box>
                  <Heading size="md">Search results</Heading>
                  <Text color={mutedText} fontSize="sm">
                    {searchResults.length} article
                    {searchResults.length === 1 ? '' : 's'} found for "{query}"
                  </Text>
                </Box>
                <Button size="sm" variant="ghost" onClick={() => setQuery('')}>
                  Clear search
                </Button>
              </Flex>

              {searchResults.length > 0 ? (
                <Stack spacing={3}>
                  {searchResults.map((result) => (
                    <Box
                      key={result.id}
                      as="button"
                      type="button"
                      textAlign="left"
                      borderWidth={1}
                      borderColor={borderColor}
                      rounded="md"
                      p={4}
                      bg={subtleCardBg}
                      _hover={{ borderColor: 'brand.400', bg: softBg }}
                      onClick={() => openAndScrollToItem(result.id)}
                    >
                      <HStack justify="space-between" align="start" spacing={4}>
                        <Box>
                          <Badge colorScheme="brand" mb={2}>
                            {result.category}
                          </Badge>
                          <Text fontWeight="bold">{result.title}</Text>
                          <Text color={mutedText} fontSize="sm" mt={1}>
                            {result.snippet}
                          </Text>
                        </Box>
                        <Icon
                          as={ChevronRightIcon}
                          boxSize={5}
                          color="brand.500"
                          flexShrink={0}
                          mt={1}
                        />
                      </HStack>
                    </Box>
                  ))}
                </Stack>
              ) : (
                <Box bg={softBg} rounded="md" p={5}>
                  <Text fontWeight="semibold">No matching article found.</Text>
                  <Text color={mutedText} mt={1}>
                    Try searching for result, payment, student, class, or report
                    sheet.
                  </Text>
                </Box>
              )}
            </Box>
          )}

          <SimpleGrid columns={{ base: 1, sm: 2, lg: 4 }} spacing={4}>
            {categories.map((category) => {
              const CategoryIcon =
                categoryIcons[category.title] ?? BookOpenIcon;
              return (
                <Box
                  key={category.title}
                  as="button"
                  type="button"
                  textAlign="left"
                  bg={panelBg}
                  borderWidth={1}
                  borderColor={borderColor}
                  rounded="lg"
                  p={4}
                  _hover={{ borderColor: 'brand.400', bg: softBg }}
                  onClick={() => scrollToCategory(category.title)}
                >
                  <HStack spacing={3} align="start">
                    <Box
                      rounded="md"
                      bg="brand.600"
                      color="white"
                      p={2}
                      flexShrink={0}
                    >
                      <Icon as={CategoryIcon} boxSize={5} />
                    </Box>
                    <Box>
                      <Text fontWeight="bold">{category.title}</Text>
                      <Text color={mutedText} fontSize="sm">
                        {category.items.length} articles
                      </Text>
                    </Box>
                  </HStack>
                </Box>
              );
            })}
          </SimpleGrid>

          <Stack spacing={6}>
            {categories.map((category) => (
              <Box
                key={category.title}
                id={`category-${normalize(category.title).replaceAll(
                  ' ',
                  '-'
                )}`}
                scrollMarginTop="24px"
              >
                <Flex align="center" justify="space-between" mb={3} gap={3}>
                  <Heading size="md">{category.title}</Heading>
                  <Badge colorScheme="brand">{category.items.length}</Badge>
                </Flex>
                <Stack spacing={3}>
                  {category.items.map((item) => {
                    const isOpen = openIds.includes(item.id);
                    const isFocused = focusedId === item.id;
                    const guidance = guidanceFor(item);

                    return (
                      <Box
                        key={item.id}
                        id={item.id}
                        bg={panelBg}
                        borderWidth={1}
                        borderColor={isFocused ? 'brand.400' : borderColor}
                        rounded="lg"
                        overflow="hidden"
                        boxShadow={
                          isFocused
                            ? '0 0 0 2px rgba(56, 189, 248, 0.25)'
                            : 'none'
                        }
                      >
                        <Button
                          variant="ghost"
                          w="full"
                          h="auto"
                          minH="56px"
                          px={4}
                          py={3}
                          justifyContent="space-between"
                          rounded="none"
                          whiteSpace="normal"
                          textAlign="left"
                          onClick={() => toggleItem(item.id)}
                        >
                          <Text fontWeight="semibold" pr={3}>
                            {item.title}
                          </Text>
                          <Icon
                            as={ChevronDownIcon}
                            boxSize={5}
                            transform={isOpen ? 'rotate(180deg)' : 'none'}
                            transition="transform 0.2s ease"
                            flexShrink={0}
                          />
                        </Button>
                        <Collapse in={isOpen} animateOpacity>
                          <Box
                            borderTopWidth={1}
                            borderColor={borderColor}
                            px={4}
                            py={4}
                          >
                            <Stack spacing={3}>
                              <Box
                                bg={softBg}
                                borderWidth={1}
                                borderColor={borderColor}
                                rounded="md"
                                p={4}
                              >
                                <Stack spacing={2}>
                                  <HStack spacing={2} align="start">
                                    <Badge colorScheme="brand" flexShrink={0}>
                                      Who uses this
                                    </Badge>
                                    <Text color={mutedText} lineHeight="1.7">
                                      {guidance.who}
                                    </Text>
                                  </HStack>
                                  <HStack spacing={2} align="start">
                                    <Badge colorScheme="green" flexShrink={0}>
                                      Where to go
                                    </Badge>
                                    <Text color={mutedText} lineHeight="1.7">
                                      {guidance.where}
                                    </Text>
                                  </HStack>
                                </Stack>
                              </Box>

                              {item.answer.split('\n\n').map((paragraph) => (
                                <Text
                                  key={paragraph}
                                  color={mutedText}
                                  lineHeight="1.8"
                                >
                                  {paragraph}
                                </Text>
                              ))}

                              <SimpleGrid
                                columns={{ base: 1, lg: 2 }}
                                spacing={4}
                                color={mutedText}
                              >
                                <DetailList
                                  title="Before you start"
                                  items={item.remember ?? guidance.before}
                                />
                                <DetailList
                                  title="Suggested steps"
                                  items={item.steps ?? guidance.steps}
                                />
                              </SimpleGrid>

                              <Box
                                borderLeftWidth={4}
                                borderColor="orange.300"
                                bg={warningBg}
                                rounded="md"
                                p={4}
                                color={mutedText}
                              >
                                <DetailList
                                  title="If something does not look right"
                                  items={guidance.troubleshooting}
                                />
                              </Box>

                              <HStack spacing={2} flexWrap="wrap">
                                {item.keywords.slice(0, 6).map((keyword) => (
                                  <Badge
                                    key={keyword}
                                    variant="subtle"
                                    colorScheme="gray"
                                  >
                                    {keyword}
                                  </Badge>
                                ))}
                              </HStack>
                            </Stack>
                          </Box>
                        </Collapse>
                      </Box>
                    );
                  })}
                </Stack>
              </Box>
            ))}
          </Stack>

          <Box
            bg={panelBg}
            borderWidth={1}
            borderColor={borderColor}
            rounded="lg"
            p={{ base: 5, md: 6 }}
          >
            <VStack spacing={3} align="start">
              <Heading size="md">Still need support?</Heading>
              <Text color={mutedText}>
                Contact your school administrator or Edumanager support with the
                school name, your role, the page you were using, the session and
                term where relevant, and a screenshot of the issue.
              </Text>
              <Text color={mutedText} fontSize="sm">
                For result, payment, admission, payroll, or attendance issues,
                include the student name, class, reference number, and the exact
                action you were trying to complete.
              </Text>
            </VStack>
          </Box>
        </Stack>
      </Container>
    </Box>
  );
}
