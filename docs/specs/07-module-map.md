# Module and feature map

This is an implementation map, not a product roadmap. Each row names the first places to inspect before changing that area.

| Module | Route entry points | Backend entry points | Frontend entry points | Data/tests |
| --- | --- | --- | --- | --- |
| Auth, user profile, institution selection | `routes/web.php` | `AuthController`, `StudentAuthController`, `Users/*`, `InstitutionRegistrationRequestController` | `pages/auth/*`, `pages/users/*` | `User`, `Institution`, auth/user controller tests |
| Manager/platform and partners | `routes/manager.php` | `Managers/*`, `Impersonate/*`, `PartnerRegistrationRequestController` | `pages/managers/*` | `InstitutionGroup`, `Partner`, `PartnerUser`, manager tests |
| Institution shell/settings | `routes/institution.php` | `Institutions/InstitutionController`, `InstitutionSettingController` | `pages/institutions/dashboard.tsx`, `institution-profile.tsx`, `settings/*` | `Institution`, `InstitutionSetting`, dashboard/settings tests |
| Staff, roles, permissions | institution users/roles routes | `Institutions/Users/*`, `Roles/RoleController`, `InstitutionRoleService` | `users/*`, `roles/*`, shared permission hooks/gate | `InstitutionUser`, `Role`, permission seeders/tests |
| Students and guardians | institution student/guardian routes, `student_routes.php` | `Staff/StudentManagementController`, `Staff/GuardianManagementController`, `Students/*`, `Guardians/*`, `RecordStudent`, `RecordGuardian` | `students/*`, `guardians/*` | `Student`, `GuardianStudent`, `Classification`, student/guardian tests |
| Classes and course teaching | institution classifications/courses routes | `Classifications/*`, `CoursesController`, `Staff/CourseTeachersController` | `classifications/*`, `class-divisions/*`, `courses/*`, staff course pages | `Classification*`, `ClassDivision`, `Course`, `CourseTeacher` |
| Curriculum and content | `routes/ccd.php` plus curriculum routes | `Curriculums/*`, `CCD/*`, `CurriculumMediaService` | `topics/*`, `scheme-of-works/*`, `lesson-plans/*`, `lesson-notes/*` | `Topic`, `CourseSession`, `Question`, `SchemeOfWork`, `LessonPlan`, `LessonNote` |
| Assessments and results | result/assessment/report routes in `institution.php` and `student_routes.php` | `Staff/*Result*`, `Results/*`, `Reports/*`, `CourseResult/*` Actions, publication controllers | `assessments/*`, `courses/*result*`, `reports/*`, `result-sheets/*`, `session-result-sheets/*` | `Assessment`, `CourseResult`, `TermResult`, `SessionResult`, result tests |
| Exams and assignments | `exam.php`, `assignment.php`, external exam routes | `Exams/*`, `Assignments/*`, exam Actions | `exams/*`, `assignments/*` | `Event`, `Exam`, courseable/question-attempt models, exam/assignment tests |
| Attendance and timetable/live classes | `attendance.php`, institution routes | `Attendance/*`, API attendance, `TimetableController`, `LiveClasses/*` | `attendances/*`, `timetables/*`, `live-classes/*` | `Attendance`, `Timetable`, `LiveClass`, attendance/live-class tests |
| Fees, payments, receipts | institution payment routes, public payment and webhook routes | `Payments/*`, `PublicStudentFeePaymentController`, payment Actions/Processors, provider controllers | `payments/*`, student payment/receipt pages, public fee page | `Fee*`, `Receipt`, `PaymentReference`, `ManualPayment`, payment tests |
| Funding, finance, payroll, withdrawals | institution/manager funding, finance, payroll routes | `Fundings/*`, `Payrolls/*`, `BankAccounts/*`, `Withdrawals/*`, settlement commands | `fundings/*`, `expenses/*`, `payrolls/*`, bank/withdrawal pages | wallet/transaction/payroll/bank/payout models and tests |
| Messaging, notifications, chats, associations | institution/manager notification/message/chat routes, API WhatsApp | `Staff/MessageController`, `Notifications/*`, `Chats/*`, `Associations/*`, messaging services/jobs | `messages/*`, `notifications/*`, `chats/index.tsx`, association pages | `Message*`, `InternalNotification*`, `Chat*`, messaging tests |
| Admissions and recruitment | public and institution admission/recruitment routes | `Admissions/*`, `Recruitment/*`, admission Actions | `admissions/*`, `recruitment/*`, auth exam login pages | admission/recruitment models, controller tests |
| Library/e-library | `routes/libraries.php` and institution route include | `Libraries/LibraryController`, media support | `libraries/*` | `Library`, `LibraryClassification`, library tests |
| Audit, media, AI assistant | `activity-logs`, `assistant.php`, upload routes | `Support/Audit/*`, `MediaManager`, `AI/*`, AI services/tools | activity-log pages, `assistant/*`, assistant component | `ActivityLog`, `Media`, AI conversation/execution/metric models and tests |

## Module notes and verified rules

### Results

Result recording is not a plain CRUD screen. It includes assessments, mid-term/full-term modes, course/class/session aggregation, comments, learning evaluations, locks, publication, activation PINs, reports, and printable templates. Inspect `RecordCourseResult`, `EvaluateCourseResultForClass`, `ProcessTermResult`, `ProcessSessionResult`, result controllers, and nearby tests together.

### Curriculum

Topics and schemes feed lesson plans and lesson notes. Lesson plans/notes can have polymorphic media attachments. Teacher ownership is checked in the controller and a plan cannot be deleted after a lesson note exists. The current routes use custom `createOrEdit`/`storeOrUpdate` endpoints rather than a conventional resource-only flow.

### Payments

Provider callbacks, public student payments, manual payment confirmation/rejection, receipts, wallet funding, settlements, and withdrawals are separate flows sharing payment records and processor abstractions. Confirm status, institution ownership, idempotency, and audit behavior before changing a payment path.

### Exams

Events can be student tests, admission exams, or recruitment tests. Exams have active/ended/pending/paused states, courseable questions, external/token entry paths, attempt activity, theory evaluation, result transfer, leaderboards, and offline mocks. Exam pages are more security-sensitive than ordinary institution CRUD.

### Messaging

Message recipient categories include institution, classes/groups, associations, single, multiple, students, and teachers. Channels include email, SMS, and WhatsApp; queued provider jobs update message status and messaging can charge institution/group wallets.

