# Data model and domain rules

## Tenancy hierarchy

The central ownership chain is:

```text
Manager / Partner
  -> InstitutionGroup
    -> Institution
      -> InstitutionUser (membership for a User)
        -> Student / Guardian / Teacher / Accountant / other staff identity
```

An institution owns or is related to classifications/classes, courses, course teachers, students, assessments, results, fees, payments, messages, notifications, curriculum, exams, attendance, payroll, and other operational records. `InstitutionGroup` also owns cross-institution platform/partner finance and branding data.

`Institution` is route-bound by UUID (`getRouteKeyName()` returns `uuid`). `User` is the shared identity model. `InstitutionUser` is the institution membership/role record and uses Spatie roles/permissions; `Student` is a separate record linked to both a user and an institution user.

## Academic structure

Core relationships confirmed by models/migrations:

- `AcademicSession` is global, soft-deletable, and can be activated through `activate()`.
- `ClassificationGroup` groups classifications; `Classification` belongs to an institution and can have students, form teacher, courses/results, class divisions, and assessments through morphable class relationships.
- `Course` belongs to an institution and relates to course sessions/topics, teachers, results, lesson notes/plans, assignments, libraries, and practice data.
- `CourseTeacher` connects a user, course, and classification.
- `Topic`, `CourseSession`, `Question`, `TheoryQuestion`, `Passage`, and `Instruction` make up course/question-bank content and use morphable courseables in several places.
- `Assessment` and `LearningEvaluation`/`LearningEvaluationDomain` configure academic scoring and extra evaluation data.
- `TermDetail` stores institution/session/term configuration including result mode and active/resumption information.

Results are layered:

1. `CourseResult` stores a student's subject score, assessment values, exam/result, grade, teacher/course/class/session/term identity, and mid-term flag.
2. `CourseResultInfo`/`ClassResultInfo` aggregate course/class result state and support locking, calculation, reports, and result sheets.
3. `TermResult` stores student/class/session/term totals, average, position, comments, activation/publication-related state, and extra evaluation data.
4. `SessionResult` stores session-level aggregate results.
5. `ResultPublication`, result settings/templates, pins, activation, reports, transcripts, and printable result-sheet pages expose results to staff, students, guardians, and public lookup flows.

`RecordCourseResult` checks `ClassResultInfo::ensureResultIsUnlocked`, merges configured assessments, computes a grade through `GetGrade`, updates the course result, records academic-integrity audit data, and can trigger class evaluation. Do not write result scores directly around this workflow without checking locks, assessment dependencies, audit behavior, and recalculation paths.

## User, student, and guardian relationships

- `User` can have many `InstitutionUser` memberships and may be a manager, partner, staff member, student, or guardian depending on role/membership context.
- `InstitutionUser` stores institution, user, type, status, roles, and related student/staff financial data.
- `Student` belongs to a classification, user, and institution user, has a unique code, and relates to results, movements, guardians, assignments, and practice.
- `GuardianStudent` links guardian user IDs to students and is institution-scoped.
- `Student::studentFees()` resolves applicable one-time, sessional, and termly fees for a student and academic context, then delegates eligibility to `Fee::forStudent()`.

## Finance and payments

The finance graph includes `Fee`, `FeeCategory`, `FeePayment`, `Receipt`, `PaymentReference`, `ManualPayment`, `Funding`, `Transaction`, `UserTransaction`, `BankAccount`, `Payout`, `Settlement`, `Withdrawal`, `PriceList`, `Commission`, and institution/group wallets.

Payment enums distinguish merchant, purpose, method, and status. Webhook controllers and payment processors handle provider-specific confirmation; `PaymentReference` is the common payable/payment-attempt record for many flows. Settlements group eligible confirmed, unsettled provider payments by institution and create settlement/withdrawal records; `settlements:process` is scheduled and tested for idempotency and per-institution failure isolation.

Fees support `one-time`, `termly`, `sessional`, and `monthly` intervals in the enum, though applicability logic should be verified in the target fee workflow before assuming every interval is handled identically.

## Messaging and notifications data

`Message` and `MessageRecipient` store institution messages, channel/status, recipient metadata, and provider state. `InternalNotification` plus read/target records handle in-app notifications and unread counts. `ChatThread`, `ChatMessage`, and `ChatThreadRead` support direct, institution, and role chat types. Messages may be sent through email, SMS, or WhatsApp and can be queued and institution-charged.

## Media and audit data

`Media` is a polymorphic, institution-aware file record with disk/path, collection, kind, visibility, status, checksum, metadata, and upload/failure timestamps. `HasMedia` and `MediaManager` are the preferred file abstraction.

`ActivityLog` is an append-oriented audit record with optional institution/group ownership, polymorphic actor/subject/impersonator, request context, old/new values, category/event/action, severity, retention category, and integrity hashes. `ModelAuditRegistry` identifies automatic audit coverage; explicit audit wrappers capture security, academic, integrity, and financial events.

## Model conventions and integrity

- Many institution-owned models use `InstitutionScope`, which adds a global `institution_id = currentInstitution()->id` condition when a current institution exists. The trait also provides `forInstitution()`.
- Many, but not all, models use `SoftDeletes`; inspect the model before relying on deleted-row behavior.
- Models use casts for enums, booleans, JSON/array values, dates, and numeric IDs. Use the cast rather than comparing a persisted enum as an unrelated string when the model exposes the enum.
- Query builders such as `InstitutionQueryBuilder` and `StudentQueryBuilder` extend Eloquent for repeated domain query behavior.
- `Relation::enforceMorphMap(MorphMap::MAP)` is enabled in `AppServiceProvider`; use the project morph-map names for polymorphic records.
- Migrations are chronological and include many later `Schema::table` changes. Follow the repository instruction: if a migration is not committed yet, it may be edited instead of adding a replacement migration. Otherwise add a new forward migration for production-safe evolution.

