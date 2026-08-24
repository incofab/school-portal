<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\EnsureTutorialInstitution;
use App\Enums\AssignmentStatus;
use App\Enums\ExamStatus;
use App\Enums\EventType;
use App\Enums\InstitutionSettingType;
use App\Enums\NoteStatusType;
use App\Enums\PaymentInterval;
use App\Enums\ReceiptStatus;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\ClassResultInfo;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\InternalNotification;
use App\Models\InternalNotificationTarget;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Receipt;
use App\Models\ResultPublication;
use App\Models\SchemeOfWork;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Seeds the read-only feature overview with representative records.
 *
 * The runner snapshots the database before this command and restores it after
 * the recording. These records exist only to make the presentation pages
 * useful to watch; the browser walkthrough never creates or changes them.
 */
class SeedFeatureOverviewTutorialData extends Command
{
  protected $signature = 'tutorial:seed-feature-overview-demo';

  protected $description = 'Create representative read-only records for the EduManager feature overview tutorial';

  public function handle(): int
  {
    [$admin, $institution] = EnsureTutorialInstitution::run();

    $adminInstitutionUser = $admin->institutionUsers()
      ->where('institution_id', $institution->id)
      ->firstOrFail();

    $academicSession = $this->academicSession($institution);
    $this->ensureAcademicSettings($institution, $academicSession);

    $classificationGroup = ClassificationGroup::factory()
      ->withInstitution($institution)
      ->create([
        'title' => 'Feature Overview Classes'
      ]);
    $classification = Classification::factory()
      ->classificationGroup($classificationGroup)
      ->create([
        'title' => 'JSS 2 Overview Class',
        'description' => 'Representative class for the feature overview'
      ]);

    $secondClassification = Classification::factory()
      ->classificationGroup($classificationGroup)
      ->create([
        'title' => 'SS 1 Overview Class',
        'description' => 'Second representative class for the feature overview'
      ]);

    $courses = collect([
      ['title' => 'Mathematics', 'code' => 'MATH-OVERVIEW', 'order' => 1],
      ['title' => 'English Language', 'code' => 'ENG-OVERVIEW', 'order' => 2]
    ])->map(
      fn(array $attributes) => Course::factory()
        ->withInstitution($institution)
        ->create($attributes)
    );

    $teacher = User::factory()
      ->teacher($institution)
      ->create([
        'first_name' => 'Grace',
        'last_name' => 'Adeyemi',
        'email' => 'feature.overview.teacher@example.com'
      ]);
    $teacherInstitutionUser = $teacher->institutionUsers()
      ->where('institution_id', $institution->id)
      ->firstOrFail();

    $students = collect([
      [
        'first_name' => 'Amina',
        'last_name' => 'Bello',
        'email' => 'feature.overview.amina@example.com',
        'code' => 'OVR0001',
        'classification' => $classification
      ],
      [
        'first_name' => 'David',
        'last_name' => 'Okafor',
        'email' => 'feature.overview.david@example.com',
        'code' => 'OVR0002',
        'classification' => $secondClassification
      ]
    ])->map(function (array $attributes) use ($institution) {
      $student = Student::factory()
        ->withInstitution($institution, $attributes['classification'])
        ->create([
          'code' => $attributes['code'],
          'guardian_phone' => '08040000001'
        ]);

      $student->user()->update([
        'first_name' => $attributes['first_name'],
        'last_name' => $attributes['last_name'],
        'email' => $attributes['email']
      ]);

      return $student->fresh(['user', 'institutionUser', 'classification']);
    });

    $student = $students->first();
    $course = $courses->first();
    $secondCourse = $courses->last();

    $this->seedFeesAndPayments(
      $institution,
      $academicSession,
      $classification,
      $student,
      $admin
    );
    $this->seedAttendance($institution, $student, $adminInstitutionUser);

    $courseTeachers = $courses->map(
      fn(Course $course) => CourseTeacher::factory()->create([
        'institution_id' => $institution->id,
        'course_id' => $course->id,
        'user_id' => $teacher->id,
        'classification_id' => $classification->id
      ])
    );

    $this->seedResults(
      $institution,
      $academicSession,
      $classification,
      $student,
      $courses,
      $teacher,
      $admin
    );
    $this->seedMessagesAndNotifications($institution, $admin, $student);
    $assignment = $this->seedAssignments(
      $institution,
      $academicSession,
      $classification,
      $student,
      $course,
      $teacherInstitutionUser
    );
    $this->seedCurriculum(
      $institution,
      $classificationGroup,
      $classification,
      $course,
      $courseTeachers->first()
    );
    $this->seedCbt(
      $institution,
      $academicSession,
      $classificationGroup,
      $classification,
      $student,
      $course,
      $secondCourse
    );

    $this->info('Feature overview fixtures ready.');
    $this->info("Institution: {$institution->name} ({$institution->uuid})");
    $this->info("Student: {$student->code}");
    $this->info("Assignment: {$assignment->id}");

    return self::SUCCESS;
  }

  private function academicSession(Institution $institution): AcademicSession
  {
    $currentSessionId = InstitutionSetting::query()
      ->where('institution_id', $institution->id)
      ->where('key', InstitutionSettingType::CurrentAcademicSession->value)
      ->value('value');

    return AcademicSession::query()->find($currentSessionId) ??
      AcademicSession::query()
        ->orderByDesc('is_active')
        ->latest('id')
        ->first() ??
      AcademicSession::factory()->active()->create([
        'title' => '2026/2027'
      ]);
  }

  private function ensureAcademicSettings(
    Institution $institution,
    AcademicSession $academicSession
  ): void {
    InstitutionSetting::query()->updateOrCreate(
      [
        'institution_id' => $institution->id,
        'key' => InstitutionSettingType::CurrentTerm->value
      ],
      ['value' => TermType::First->value]
    );
    InstitutionSetting::query()->updateOrCreate(
      [
        'institution_id' => $institution->id,
        'key' => InstitutionSettingType::CurrentAcademicSession->value
      ],
      ['value' => $academicSession->id]
    );
  }

  private function seedFeesAndPayments(
    Institution $institution,
    AcademicSession $academicSession,
    Classification $classification,
    Student $student,
    User $admin
  ): void {
    $fee = Fee::factory()
      ->institution($institution)
      ->create([
        'title' => 'Feature Overview Tuition Fee',
        'amount' => 35000,
        'payment_interval' => PaymentInterval::Termly->value,
        'term' => TermType::First->value,
        'academic_session_id' => $academicSession->id,
        'fee_items' => [
          ['title' => 'Tuition', 'amount' => 30000],
          ['title' => 'Learning materials', 'amount' => 5000]
        ]
      ]);
    FeeCategory::factory()
      ->fee($fee)
      ->feeable($classification)
      ->create();

    $receipt = Receipt::factory()
      ->fee($fee)
      ->student($student)
      ->create([
        'institution_id' => $institution->id,
        'user_id' => $student->user_id,
        'fee_id' => $fee->id,
        'academic_session_id' => $academicSession->id,
        'term' => TermType::First->value,
        'amount' => 35000,
        'amount_paid' => 15000,
        'amount_remaining' => 20000,
        'status' => ReceiptStatus::Partial->value
      ]);

    FeePayment::factory()
      ->fee($fee)
      ->receipt($receipt)
      ->create([
        'institution_id' => $institution->id,
        'amount' => 15000,
        'method' => 'bank-transfer',
        'reference' => Str::orderedUuid()->toString(),
        'confirmed_by_user_id' => $admin->id
      ]);
  }

  private function seedAttendance(
    Institution $institution,
    Student $student,
    $adminInstitutionUser
  ): void {
    Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->create([
        'institution_id' => $institution->id,
        'institution_staff_user_id' => $adminInstitutionUser->id,
        'remark' => 'Arrived before the first lesson.',
        'signed_in_at' => now()->subHours(4),
        'signed_out_at' => now()->subHours(1)
      ]);
  }

  private function seedResults(
    Institution $institution,
    AcademicSession $academicSession,
    Classification $classification,
    Student $student,
    $courses,
    User $teacher,
    User $admin
  ): void {
    Assessment::query()->where('institution_id', $institution->id)->exists() ||
      Assessment::factory()
        ->withInstitution($institution)
        ->count(2)
        ->create();

    $publication = ResultPublication::factory()->create([
      'institution_id' => $institution->id,
      'institution_group_id' => $institution->institution_group_id,
      'academic_session_id' => $academicSession->id,
      'staff_user_id' => $admin->id,
      'term' => TermType::First->value,
      'num_of_results' => 2
    ]);

    ClassResultInfo::factory()->create([
      'institution_id' => $institution->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'num_of_students' => 2,
      'num_of_courses' => $courses->count(),
      'total_score' => 148,
      'average' => 74,
      'max_score' => 90,
      'min_score' => 58,
      'max_obtainable_score' => 200
    ]);

    $termResult = TermResult::factory()->create([
      'institution_id' => $institution->id,
      'student_id' => $student->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'total_score' => 148,
      'position' => 1,
      'average' => 74,
      'remark' => 'A strong and consistent term.',
      'is_activated' => true,
      'result_publication_id' => $publication->id
    ]);

    foreach ($courses as $index => $course) {
      CourseResultInfo::factory()
        ->withInstitution(
          $institution,
          $classification,
          $course,
          $academicSession
        )
        ->create([
          'term' => TermType::First->value,
          'for_mid_term' => false,
          'num_of_students' => 2,
          'total_score' => $index === 0 ? 78 : 70,
          'max_obtainable_score' => 100,
          'max_score' => $index === 0 ? 84 : 76,
          'min_score' => $index === 0 ? 54 : 49,
          'average' => $index === 0 ? 78 : 70
        ]);

      CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'student_id' => $student->id,
        'teacher_user_id' => $teacher->id,
        'course_id' => $course->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $academicSession->id,
        'term' => TermType::First->value,
        'for_mid_term' => false,
        'exam' => $index === 0 ? 48 : 42,
        'result' => $index === 0 ? 78 : 70,
        'remark' => 'Good progress this term.'
      ]);
    }

    SessionResult::factory()->create([
      'institution_id' => $institution->id,
      'student_id' => $student->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'result' => 74,
      'average' => 74,
      'remark' => 'A positive session overall.'
    ]);

    // Keep the generated term result referenced so the factory-created course
    // result is not optimized away by static analysis or future refactors.
    $termResult->refresh();
  }

  private function seedMessagesAndNotifications(
    Institution $institution,
    User $admin,
    Student $student
  ): void {
    $message = Message::factory()
      ->institution($institution)
      ->create([
        'subject' => 'Feature overview welcome message',
        'body' => 'A representative sent message for the EduManager walkthrough.',
        'sent_at' => now()
      ]);
    MessageRecipient::factory()
      ->message($message)
      ->recipient($student->user)
      ->create();

    $notification = InternalNotification::factory()
      ->withInstitution($institution)
      ->sender($admin)
      ->create([
        'title' => 'Feature overview notification',
        'body' => 'A representative notification for the walkthrough.'
      ]);
    InternalNotificationTarget::factory()
      ->notification($notification)
      ->notifiable($admin)
      ->create();
  }

  private function seedAssignments(
    Institution $institution,
    AcademicSession $academicSession,
    Classification $classification,
    Student $student,
    Course $course,
    $teacherInstitutionUser
  ): Assignment {
    $assignment = Assignment::factory()->create([
      'institution_id' => $institution->id,
      'course_id' => $course->id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::First->value,
      'status' => AssignmentStatus::Active->value,
      'max_score' => 20,
      'content' => 'Read the short passage and explain its main idea.',
      'expires_at' => now()->addDays(10),
      'institution_user_id' => $teacherInstitutionUser->id
    ]);
    $assignment->classifications()->attach($classification->id, [
      'institution_id' => $institution->id
    ]);

    AssignmentSubmission::factory()->create([
      'assignment_id' => $assignment->id,
      'student_id' => $student->id,
      'answer' => 'The main idea is that steady practice improves understanding.',
      'score' => 17
    ]);

    return $assignment;
  }

  private function seedCurriculum(
    Institution $institution,
    ClassificationGroup $classificationGroup,
    Classification $classification,
    Course $course,
    CourseTeacher $courseTeacher
  ): void {
    $topic = Topic::factory()
      ->course($course)
      ->classificationGroup($classificationGroup)
      ->create([
        'title' => 'Feature Overview: Fractions',
        'description' => 'Represent, compare, and solve simple fractions.'
      ]);
    $schemeOfWork = SchemeOfWork::factory()
      ->topic($topic)
      ->create([
        'term' => TermType::First->value,
        'week_number' => 3,
        'learning_objectives' => 'Understand equivalent fractions and simple comparisons.',
        'resources' => 'Textbook, board illustrations, and practice cards.'
      ]);
    $lessonPlan = LessonPlan::factory()
      ->schemeOfWork($schemeOfWork)
      ->create([
        'institution_id' => $institution->id,
        'course_teacher_id' => $courseTeacher->id,
        'objective' => 'Learners identify equivalent fractions.',
        'activities' => 'Guided examples followed by paired practice.',
        'content' => 'Use visual models to compare parts of a whole.'
      ]);
    LessonNote::factory()
      ->lessonPlan($lessonPlan, $classification)
      ->courseTeacher($courseTeacher)
      ->create([
        'institution_id' => $institution->id,
        'classification_group_id' => $classificationGroup->id,
        'classification_id' => $classification->id,
        'course_id' => $course->id,
        'topic_id' => $topic->id,
        'term' => TermType::First->value,
        'title' => 'Fractions lesson note',
        'content' => 'A concise note on equivalent fractions and classroom examples.',
        'status' => NoteStatusType::Published->value
      ]);
  }

  private function seedCbt(
    Institution $institution,
    AcademicSession $academicSession,
    ClassificationGroup $classificationGroup,
    Classification $classification,
    Student $student,
    Course $course,
    Course $secondCourse
  ): void {
    $courseSession = CourseSession::factory()
      ->course($course)
      ->create([
        'institution_id' => $institution->id,
        'session' => 'Overview Questions'
      ]);
    $event = Event::factory()
      ->institution($institution)
      ->create([
        'title' => 'Feature Overview CBT Exam',
        'description' => 'Representative internal school computer-based test.',
        'duration' => 30,
        'status' => 'active',
        'num_of_activations' => 10,
        'num_of_subjects' => 1,
        'starts_at' => now()->subMinutes(10),
        'type' => EventType::StudentTest->value,
        'classification_group_id' => $classificationGroup->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $academicSession->id,
        'term' => TermType::First->value
      ]);
    EventCourseable::factory()
      ->event($event, $courseSession)
      ->create();

    $exam = Exam::factory()
      ->started()
      ->event($event)
      ->examable($student)
      ->create([
        'institution_id' => $institution->id,
        'num_of_questions' => 2,
        'status' => ExamStatus::Active
      ]);
    ExamCourseable::factory()
      ->exam($exam)
      ->courseable($courseSession)
      ->create([
        'num_of_questions' => 2
      ]);

    // A second course session keeps the question/content area representative
    // without making the browser start or answer an exam.
    CourseSession::factory()
      ->course($secondCourse)
      ->create([
        'institution_id' => $institution->id,
        'session' => 'Overview Practice'
      ]);
  }
}
