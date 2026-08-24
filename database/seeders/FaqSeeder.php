<?php

namespace Database\Seeders;

use App\Enums\FaqType;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
  public function run(): void
  {
    $contents = [
      ...array_map(
        fn(array $faq) => [...$faq, 'type' => FaqType::Faq->value],
        $this->faqs()
      ),
      ...array_map(
        fn(array $article) => [
          ...$article,
          'type' => FaqType::KnowledgeBase->value
        ],
        $this->knowledgeBaseArticles()
      )
    ];

    foreach ($contents as $content) {
      Faq::query()->updateOrCreate(['code' => $content['code']], $content);
    }
  }

  private function faqs(): array
  {
    return [
      [
        'name' => 'How do I log in and find my dashboard?',
        'code' => 'login-main-dashboard',
        'description' => '<p>Open the Edumanager login page and enter your registered email or phone number with your password. After login, Edumanager sends you to the correct dashboard based on your role.</p>
<p>Managers see the manager dashboard. Institution admins, teachers, accountants, guardians, students, and alumni see their school dashboard. Use the left menu to open features such as Students, Classes, Subject, Results, Payments, Attendance, Chats, and Settings.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 1
      ],
      [
        'name' => 'What should I do if I forgot my password?',
        'code' => 'forgot-password',
        'description' => '<p>Use the Forgot Password link on the login page. Enter your registered email or phone number and follow the reset instruction sent by the school or platform.</p>
<p>If you cannot receive the reset message, contact your school administrator to confirm that your user profile has the correct contact details.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 2
      ],
      [
        'name' => 'Why can I not see a feature on my dashboard?',
        'code' => 'roles-permissions',
        'description' => '<p>Edumanager shows menu items based on your role. An admin can manage most school settings. Teachers usually see subject, class, result, assignment, lesson, attendance, chat, and student-related tools. Accountants see payment, fee, receipt, wallet, bank, and finance tools. Students and guardians see their own results, fees, classes, assignments, chats, and dependents where enabled.</p>
<p>If a feature is missing, ask an admin to check your role and user association.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 3
      ],
      [
        'name' => 'How do I switch between schools or institutions?',
        'code' => 'switch-institution',
        'description' => '<p>If your account belongs to more than one institution, Edumanager shows an institution selection screen after login. Choose the school you want to work in.</p>
<p>If you expected another school but cannot see it, ask the school admin to add your user account to that institution.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 4
      ],
      [
        'name' => 'How do I change my password after logging in?',
        'code' => 'change-password',
        'description' => '<p>Open the profile menu in the dashboard header and choose Change Password. Enter your current password, then set the new password.</p>
<p>Use a password that is easy for you to remember but difficult for others to guess. Do not share staff or admin passwords.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 5
      ],
      [
        'name' => 'How do I set the current academic session and term?',
        'code' => 'setup-current-session-term',
        'description' => '<p>Admins should open Settings or Institution Settings and set the current academic session and current term. These settings affect result entry, attendance, dashboards, payments, and report generation.</p>
<p>Always confirm the current session and term before teachers begin recording scores or accountants begin creating term-based fees.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 6
      ],
      [
        'name' => 'How do I create a new academic session?',
        'code' => 'create-academic-session',
        'description' => '<p>Open Academic Sessions from the manager or school administration area, then create the new session title, such as 2024/2025. After creating it, set it as the current academic session in institution settings when the school is ready to use it.</p>
<p>Do not delete old sessions that already have results, payments, attendance, or student movement records.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 7
      ],
      [
        'name' => 'Where do I set term details and resumption dates?',
        'code' => 'term-details-resumption',
        'description' => '<p>Open Term Details or Class Result Analysis, depending on your school workflow. Admins or authorized result officers can set resumption dates and term-level details used on report sheets.</p>
<p>Confirm these details before printing or publishing results so report sheets show the correct next-term information.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 8
      ],
      [
        'name' =>
          'What is the difference between mid-term and full-term result mode?',
        'code' => 'mid-term-full-term',
        'description' => '<p>Mid-term mode is for scores recorded before the end-of-term exam. Full-term mode is for final term results that usually include continuous assessment and exam scores.</p>
<p>If your school uses mid-term results, choose the correct mode before recording, uploading, calculating, or checking results. This prevents scores from entering the wrong result bucket.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 9
      ],
      [
        'name' => 'How do I add a new student?',
        'code' => 'add-student',
        'description' => '<p>Open Students, then choose Add Student. Enter the student bio-data, class, admission number or code, guardian details, contact information, and any required profile fields.</p>
<p>After saving, confirm the student appears in the correct class. If the student should log in, share the student login details or ask the admin to confirm the account credentials.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 10
      ],
      [
        'name' => 'How do I update a student profile?',
        'code' => 'edit-student-profile',
        'description' => '<p>Open Students, search for the student, then open the student profile or edit page. Update the required details and save.</p>
<p>Be careful when changing class, admission number, guardian phone, or status because those fields can affect result lookup, payments, WhatsApp result checking, and guardian access.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 11
      ],
      [
        'name' => 'How do I move a student to another class?',
        'code' => 'student-class-change',
        'description' => '<p>Use Student Class Changes or the student movement feature under Classes or Students. Select the student, the old class, the new class, session, term, and reason for the movement.</p>
<p>Use promotion tools when moving many students at the end of a session. Use individual class movement for corrections, transfers, or special cases.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 12
      ],
      [
        'name' => 'How do guardians see their children or dependents?',
        'code' => 'guardian-dependent',
        'description' => '<p>Guardians must have their phone number or user account linked to the student record. After login, guardians can open Dependents to see linked students.</p>
<p>If a guardian cannot see a child, check that the guardian phone number and user association match the student record.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 13
      ],
      [
        'name' => 'How do admission forms and applications work?',
        'code' => 'admission-forms',
        'description' => '<p>Admins create admission forms from the Admissions menu. Applicants can buy or complete the form through the public admission link when enabled. Staff can review submitted applications, preview details, approve successful applicants, and generate admission letters.</p>
<p>Use admission forms for new intakes instead of manually collecting applicant information outside the platform.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 14
      ],
      [
        'name' =>
          'What should I do when a student graduates, leaves, or is suspended?',
        'code' => 'student-status',
        'description' => '<p>Update the student status from the student management area. Use alumni or graduated status for completed students, suspended status for temporary access restriction, and transfer or inactive status where your school process requires it.</p>
<p>Avoid deleting students who already have payments, results, attendance, or receipts. Status changes preserve history.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 15
      ],
      [
        'name' => 'How do I create classes and class divisions?',
        'code' => 'create-classes',
        'description' => '<p>Open Classes, then create the main class such as JSS 1 or Primary 4. If your school uses arms or divisions such as A, B, Red, or Blue, open Class Divisions and create them under the correct class.</p>
<p>Use class groups when you need broader result grouping, promotion grouping, or reporting across related classes.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 16
      ],
      [
        'name' => 'How do I create subjects or courses?',
        'code' => 'create-subjects',
        'description' => '<p>Open Subject, then choose Add Subject. Enter the subject title and save. Admins can create subjects one by one or use bulk tools where available.</p>
<p>After creating subjects, assign teachers and map subjects to the classes where they should be taught.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 17
      ],
      [
        'name' => 'How do I assign a teacher to a subject?',
        'code' => 'assign-subject-teacher',
        'description' => '<p>Open Subject Teachers from the Subject menu. Choose the teacher, subject, class, session, and term if required. Save the assignment.</p>
<p>Teachers usually see result entry, assignments, lesson plans, and class tools only for subjects or classes assigned to them.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 18
      ],
      [
        'name' => 'How do form teachers or class teachers work?',
        'code' => 'form-teacher-class-teacher',
        'description' => '<p>A form teacher is a staff member responsible for a class. Depending on school permissions, form teachers can view class students, record comments, check attendance, review class result analysis, or help with report sheets.</p>
<p>If a teacher cannot access class result tools, confirm that they are assigned to the class or have the correct role.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 19
      ],
      [
        'name' =>
          'Where do teachers manage scheme of work, lesson plans, and lesson notes?',
        'code' => 'scheme-lesson-plan-note',
        'description' => '<p>Teachers and admins can use Scheme of Works, Lesson Plans, Lesson Notes, Topics, and Sub-topics from the academic menus. These tools help structure what should be taught and track lesson preparation.</p>
<p>Use topics and schemes before lesson plans when your school wants organized curriculum coverage by subject, class, term, and week.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 20
      ],
      [
        'name' => 'How do teachers record student results?',
        'code' => 'record-result',
        'description' => '<p>Open Subject, then choose the relevant result recording page such as Record Course Result, Record Class Course Result, or Record Student Subject Results. Select the subject teacher, class, session, term, and result type.</p>
<p>Enter assessment and exam scores carefully, then save. If mid-term is enabled, choose full-term or mid-term before recording so scores enter the correct result type.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 21
      ],
      [
        'name' => 'Can I upload results from Excel?',
        'code' => 'upload-result-sheet',
        'description' => '<p>Yes. Use the Upload Class Sheet or course result upload tools where available. Download the correct template first, fill scores without changing the required columns, then upload it back.</p>
<p>If the upload fails, check that student codes, subject mappings, class, session, term, and result type match the template.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 22
      ],
      [
        'name' => 'How do I calculate or process results?',
        'code' => 'calculate-results',
        'description' => '<p>After teachers finish recording scores, open Class Result Analysis or Course Result Info and run calculation or processing. This generates aggregates, grades, positions, and term result records.</p>
<p>Make sure all required subject scores are entered before processing. If a score is missing, correct it first or use the school-approved option to calculate with missing scores if available.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 23
      ],
      [
        'name' => 'Why is a student result missing or incomplete?',
        'code' => 'missing-result-score',
        'description' => '<p>A result may be missing because scores were not recorded, the wrong session or term was selected, the student was in the wrong class, the result was recorded as mid-term instead of full-term, or the class result has not been processed.</p>
<p>Check the student class, subject teacher assignment, current session and term, result type, and processing status.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 24
      ],
      [
        'name' => 'How do I publish results for students and guardians?',
        'code' => 'publish-results',
        'description' => '<p>Use Result Publications after results have been checked and approved. Select the session, term, class or student scope, then publish. Published results become available to students and guardians based on your school settings.</p>
<p>Do not publish results until comments, grades, positions, resumption dates, and principal approval are correct.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 25
      ],
      [
        'name' => 'How do I print or download report sheets?',
        'code' => 'print-report-sheets',
        'description' => '<p>Open the student result sheet, class result sheet, multiple result sheets, transcript, or session result page. Use the print or download button provided on the result page.</p>
<p>If the layout looks wrong, check the selected result template, class division template, paper size, browser print settings, and whether exam score display is enabled.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 26
      ],
      [
        'name' => 'Where do teacher and principal comments come from?',
        'code' => 'result-comments',
        'description' => '<p>Comments can be typed manually, generated from templates, or added through class result tools depending on your school setup. Result comment templates help schools keep comments consistent.</p>
<p>Before publishing, review comments for spelling, tone, and student-specific accuracy.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 27
      ],
      [
        'name' => 'Why is a result asking for an activation PIN?',
        'code' => 'result-pin-activation',
        'description' => '<p>Some schools require result activation before students or guardians can view report sheets. Enter the result activation PIN on the result activation page or through the WhatsApp result checker when prompted.</p>
<p>If the PIN is rejected, confirm that it belongs to the right school group, has not exceeded its usage limit, and matches the student or session rules.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 28
      ],
      [
        'name' => 'How do I create school fees or payment items?',
        'code' => 'fees-payments',
        'description' => '<p>Accountants or admins can open Fees and create fee items for tuition, admission, transport, hostel, exam, or other charges. Assign the fee to the correct class, session, term, or student group according to your school process.</p>
<p>Use clear fee titles so parents and staff understand what each payment is for.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 29
      ],
      [
        'name' => 'How do I record a manual payment?',
        'code' => 'record-manual-payment',
        'description' => '<p>Open Manual Payments or Fee Payments, choose the student and fee, enter the amount paid, payment method, reference, and payment date, then save or approve based on your workflow.</p>
<p>Pending manual payments should be reviewed before they affect student balances or receipts.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 30
      ],
      [
        'name' => 'How do I print a receipt?',
        'code' => 'print-receipt',
        'description' => '<p>Open Receipts or Fee Payments, search for the student or payment reference, then open the receipt page. Use the browser print option or the available print button.</p>
<p>If a receipt is missing, confirm that the payment was approved and tied to the correct student and fee.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 31
      ],
      [
        'name' => 'How do I check a student balance?',
        'code' => 'payment-balance',
        'description' => '<p>Open Fee Payment Summary, Fee Payments, or the student payment view. Search for the student and review assigned fees, paid amounts, outstanding balances, receipts, and payment history.</p>
<p>If the balance looks wrong, check duplicate fees, wrong session or term, unapproved manual payments, and payments recorded against the wrong student.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 32
      ],
      [
        'name' => 'How do online payments work?',
        'code' => 'online-payment',
        'description' => '<p>When payment gateway keys are configured, students or guardians can pay online from the payment page. Edumanager records successful payment callbacks and links them to the relevant payment reference.</p>
<p>If online payments are not working, check payment key settings, callback configuration, school bank setup, and the payment provider dashboard.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 33
      ],
      [
        'name' => 'What is the wallet used for?',
        'code' => 'wallet-funding',
        'description' => '<p>Institution and group wallets track platform credits, service charges, funding, deductions, and related transactions. Managers and authorized admins can view wallet activity from Funding or Transactions.</p>
<p>Keep wallet funding records clear so billing, message charges, result publication charges, or service deductions can be audited.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 34
      ],
      [
        'name' => 'How do bank accounts and withdrawals work?',
        'code' => 'withdrawals-bank-accounts',
        'description' => '<p>Add verified bank accounts before requesting withdrawals. Authorized users can create withdrawal requests, and managers can review or update withdrawal status.</p>
<p>If verification fails, confirm the bank, account number, account name, and any required BVN or NIN details.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 35
      ],
      [
        'name' => 'Where can partners or managers view commissions?',
        'code' => 'partner-commissions',
        'description' => '<p>Managers and partners can open Commissions from the manager dashboard. Commission records usually relate to institution registration, subscription, funding, or platform business rules.</p>
<p>If a commission is missing, check whether the linked transaction has been completed and whether the partner relationship is correctly recorded.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 36
      ],
      [
        'name' => 'How do I take attendance?',
        'code' => 'take-attendance',
        'description' => '<p>Open Attendance and choose the class, date, session, and term. Mark students as present, absent, late, or the status your school uses, then save.</p>
<p>Use the class attendance register or attendance reports to review patterns and follow up on frequent absences.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 37
      ],
      [
        'name' => 'How do I view attendance reports?',
        'code' => 'attendance-report',
        'description' => '<p>Open Attendance Reports or Student Attendance Report. Select the class, student, date range, session, and term as needed.</p>
<p>Reports help form teachers, admins, and school owners track punctuality, absences, and class attendance trends.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 38
      ],
      [
        'name' => 'Where do I manage timetables and school activities?',
        'code' => 'timetable-activities',
        'description' => '<p>Use Timetables for class or school schedules. Use School Activities for events, programs, reminders, and school-wide activities.</p>
<p>Keep schedules current so teachers and students can plan lessons, assessments, live classes, and school events.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 39
      ],
      [
        'name' =>
          'How do to-do items and live classes help staff and students?',
        'code' => 'todo-live-class',
        'description' => '<p>To-do items help staff track internal tasks. Live Classes help schools organize online class sessions where enabled.</p>
<p>Teachers should add clear titles, dates, class or subject information, and links or instructions so students know what to join or complete.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 40
      ],
      [
        'name' => 'How do I send messages or notifications?',
        'code' => 'send-message',
        'description' => '<p>Open Messages or Notifications, choose the target audience, write the message, and send. Depending on setup, Edumanager can support internal notifications, SMS, WhatsApp, and other channels.</p>
<p>Use clear message titles and avoid sending sensitive student or payment information to the wrong audience.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 41
      ],
      [
        'name' => 'How do chats work inside Edumanager?',
        'code' => 'chat-users',
        'description' => '<p>Open Chats from the dashboard. You can search conversations, start a new chat, open existing threads, and reply to messages. Unread counts help you know which conversations need attention.</p>
<p>School staff can use chats for guardian support, student questions, staff coordination, and internal follow-up.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 42
      ],
      [
        'name' => 'How does WhatsApp result checking work?',
        'code' => 'whatsapp-result-checker',
        'description' => '<p>When WhatsApp integration is configured, guardians can message the school WhatsApp number to check results. Edumanager matches the sender phone number to linked students, asks the user to choose a student if needed, checks publication and activation status, then sends a signed result link.</p>
<p>If the student is not found, confirm the guardian phone number on the student profile.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 43
      ],
      [
        'name' =>
          'What should I check before sending bulk SMS or WhatsApp messages?',
        'code' => 'bulk-sms',
        'description' => '<p>Confirm the recipient list, message content, sender balance or wallet, and channel settings. Send short, clear messages and avoid unnecessary repeats.</p>
<p>If messages fail, check phone number format, wallet balance, provider credentials, and whether the message job has been processed.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 44
      ],
      [
        'name' => 'How do teachers create assignments?',
        'code' => 'assignments',
        'description' => '<p>Open Assignments and choose Create Assignment. Select the class or students, subject, due date, instructions, and any attachment if supported. Students can view the assignment and submit work according to your school workflow.</p>
<p>Teachers can review submissions from Assignment Submissions.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 45
      ],
      [
        'name' => 'How do CBT exams work?',
        'code' => 'exam-cbt',
        'description' => '<p>Admins or authorized staff create exams or events, add subjects, configure questions, set schedules, and publish or start the exam when ready. Students log in through the exam page and answer objective or theory questions based on the exam setup.</p>
<p>After the exam, staff can view results, transfer scores, and evaluate theory answers where required.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 46
      ],
      [
        'name' => 'Where do I manage questions and practice content?',
        'code' => 'question-bank',
        'description' => '<p>Use question bank, CCD, topics, and practice question tools where enabled. Organize questions by subject, topic, class, and type so they can be reused in exams or student practice.</p>
<p>Good question organization makes CBT setup faster and improves practice tracking.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 47
      ],
      [
        'name' => 'Can exam scores be transferred into term results?',
        'code' => 'transfer-exam-result',
        'description' => '<p>Yes, authorized users can transfer matching exam or event scores into course or term result records when the exam setup and student mappings are correct.</p>
<p>Before transfer, confirm student codes, subject mappings, class, session, term, and result type. After transfer, recalculate result summaries if required.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 48
      ],
      [
        'name' => 'How do payroll and salaries work?',
        'code' => 'payroll',
        'description' => '<p>Admins can create salary types, salary records, payroll adjustments, and payroll summaries. Use salary components for allowances, deductions, bonuses, or recurring staff payment structure.</p>
<p>Review payroll carefully before approval because it affects financial reports and staff payment history.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 49
      ],
      [
        'name' => 'How do I record school expenses?',
        'code' => 'expenses',
        'description' => '<p>Open Expenses, create expense categories if needed, then record each expense with date, amount, category, description, and supporting details.</p>
<p>Use consistent categories so school owners can understand spending patterns and compare expenses over time.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 50
      ],
      [
        'name' => 'Can admins see important changes made in the system?',
        'code' => 'activity-logs',
        'description' => '<p>Yes. Activity Logs show important actions such as result changes, payments, publications, PIN usage, messages, and other administrative events where logging is enabled.</p>
<p>Use audit logs to investigate disputes, corrections, unauthorized changes, and operational history.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 51
      ],
      [
        'name' => 'Why does Edumanager ask for BVN, NIN, or bank details?',
        'code' => 'bank-bvn-nin',
        'description' => '<p>Some payment, wallet, reserved account, withdrawal, or verification workflows require valid identity or bank information. Only authorized users should update these details.</p>
<p>If account verification fails, confirm the exact name, bank, account number, BVN, NIN, and provider requirements.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 52
      ],
      [
        'name' => 'What can I import or export?',
        'code' => 'imports-exports',
        'description' => '<p>Edumanager supports several imports and exports such as students, staff, result sheets, pins, class lists, payment records, reports, and printable outputs depending on the module.</p>
<p>Always download the correct template before uploading. Keep column names and student codes unchanged unless the template instructs otherwise.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 53
      ],
      [
        'name' => 'How do I print student or staff ID cards?',
        'code' => 'print-id-card',
        'description' => '<p>Open Students or Staff, then choose the ID card option if enabled. Select the class, staff group, or students to print.</p>
<p>Check names, photos, class, codes, and school logo before printing in bulk.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 54
      ],
      [
        'name' => 'How do I generate student transcripts?',
        'code' => 'transcripts',
        'description' => '<p>Open the transcript or cumulative result area for the student. Select the relevant sessions and classes, then generate or print the transcript.</p>
<p>Transcripts depend on historical results, so make sure previous term and session results were correctly processed.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 55
      ],
      [
        'name' =>
          'What should I do when uploaded files or documents do not show?',
        'code' => 'documents-files',
        'description' => '<p>Check the file size, file type, internet connection, and whether the save action completed successfully. For images, use clear JPG or PNG files where possible.</p>
<p>If a file was uploaded but not visible, refresh the page and confirm the record was saved to the correct student, staff, assignment, lesson note, or application.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 56
      ],
      [
        'name' =>
          'Where do I change result templates and result display settings?',
        'code' => 'result-settings',
        'description' => '<p>Open Result Settings from Institution Settings. Admins can choose the default result template, whether positions should display, how exam scores appear, and other report sheet behavior.</p>
<p>Preview the result template before publishing or printing many reports.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 57
      ],
      [
        'name' => 'Where do I configure payment keys and payment settings?',
        'code' => 'payment-settings',
        'description' => '<p>Open Payment Keys or Payment Settings. Add the required public and private keys for the payment provider your school uses.</p>
<p>Only trusted admins should manage payment keys. Incorrect keys can cause online payments or callbacks to fail.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 58
      ],
      [
        'name' => 'Why are records showing under the wrong session or term?',
        'code' => 'current-session-wrong',
        'description' => '<p>Most academic records depend on the selected or current session and term. If records appear in the wrong place, check institution settings and the filters on the page you are using.</p>
<p>Correct the current session and term before creating new results, attendance, fees, assignments, or class records.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 59
      ],
      [
        'name' => 'How do I use search and filters on list pages?',
        'code' => 'search-filter-pages',
        'description' => '<p>Most list pages include search, filters, pagination, and action buttons. Search by name, code, title, reference, or other visible values. Use filters for class, session, term, status, date, or role where available.</p>
<p>If you cannot find a record, clear filters and search with fewer words.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 60
      ],
      [
        'name' => 'What should I send support when I still need help?',
        'code' => 'support-next-step',
        'description' => '<p>Send the school name, your role, the page you were using, what you expected, what happened, the student or payment reference if relevant, and a screenshot.</p>
<p>For result, payment, admission, or payroll issues, include the session, term, class, student name, and exact action you were trying to complete.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 61
      ]
    ];
  }

  private function knowledgeBaseArticles(): array
  {
    return [
      [
        'name' => 'Getting started with your school workspace',
        'code' => 'guide-school-workspace-start',
        'description' => '<p>Start by confirming your institution profile, current academic session, and current term. These settings provide context for the school records your team creates.</p>
<p>Next, create classes, subjects, staff assignments, and user associations before inviting teachers, accountants, guardians, or students to use the platform.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 1
      ],
      [
        'name' => 'Recommended result-processing workflow',
        'code' => 'guide-result-processing-workflow',
        'description' => '<p>Set the session and term, confirm classes and subject-teacher assignments, record or upload scores, then review missing scores before processing class results.</p>
<p>Review comments and report settings before publishing. Only publish after the school has confirmed that the results and student details are correct.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 2
      ],
      [
        'name' => 'Setting up fees and collecting payments',
        'code' => 'guide-fees-and-payments',
        'description' => '<p>Create clear fee items for the correct class, session, term, or student group. Review assignments before recording payments so balances remain accurate.</p>
<p>For manual payments, capture the student, fee, amount, method, reference, and approval status. Keep receipts and payment history available for follow-up.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 3
      ],
      [
        'name' => 'Preparing attendance and class records',
        'code' => 'guide-attendance-class-records',
        'description' => '<p>Confirm the class, date, session, and term before taking attendance. Mark each student using the statuses supported by your school workflow and save the register.</p>
<p>Use attendance reports to review patterns and follow up with families when needed. Keep class and student records current so attendance remains reliable.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 4
      ],
      [
        'name' => 'Preparing a support request that can be resolved quickly',
        'code' => 'guide-effective-support-request',
        'description' => '<p>Include the school name, your role, the page and action involved, what you expected, what happened, and a screenshot when contacting support.</p>
<p>For result, payment, admission, or payroll issues, also include the relevant session, term, class, student name, payment reference, or other safe identifying context.</p>',
        'video_url' => null,
        'is_active' => true,
        'sort_order' => 5
      ]
    ];
  }
}
