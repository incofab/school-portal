<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\SaveInstitutionSetting;
use App\Actions\Tutorials\EnsureTutorialInstitution;
use App\Enums\ExamStatus;
use App\Enums\EventType;
use App\Enums\InstitutionSettingType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Seeds the isolated records used by the hands-on result-recording tutorial.
 *
 * The tutorial runner snapshots the database before this command and restores
 * it after recording. Browser actions intentionally create result data during
 * the walkthrough; those changes are discarded with the snapshot restore.
 */
class SeedResultRecordingWorkflowTutorialData extends Command
{
  protected $signature = 'tutorial:seed-result-recording-workflow-demo';

  protected $description = 'Create the isolated fixtures used by the result-recording tutorial';

  public function handle(): int
  {
    [, $institution] = EnsureTutorialInstitution::run();
    $academicSession = $this->academicSession($institution);
    $this->ensureAcademicSettings($institution, $academicSession);

    $classificationGroup = ClassificationGroup::factory()
      ->withInstitution($institution)
      ->create(['title' => 'Result Workflow Demo Classes']);

    $individualClass = $this->classification(
      $classificationGroup,
      'JSS 2 Result Recording'
    );
    $excelClass = $this->classification(
      $classificationGroup,
      'SS 1 Excel Import'
    );
    $cbtClass = $this->classification(
      $classificationGroup,
      'JSS 3 CBT Transfer'
    );

    $mathematics = Course::factory()
      ->withInstitution($institution)
      ->create([
        'title' => 'Result Workflow Mathematics',
        'code' => 'RWMATH',
        'order' => 1
      ]);
    $english = Course::factory()
      ->withInstitution($institution)
      ->create([
        'title' => 'Result Workflow English',
        'code' => 'RWENG',
        'order' => 2
      ]);

    $teacher = User::factory()
      ->teacher($institution)
      ->create([
        'first_name' => 'Result',
        'last_name' => 'Workflow Teacher',
        'other_names' => '',
        'email' => 'result.workflow.teacher@example.com'
      ]);

    $students = collect([
      [
        'first_name' => 'Amina',
        'last_name' => 'Result',
        'email' => 'result.workflow.amina@example.com',
        'code' => 'RWR0001',
        'classification' => $individualClass
      ],
      [
        'first_name' => 'David',
        'last_name' => 'Result',
        'email' => 'result.workflow.david@example.com',
        'code' => 'RWR0002',
        'classification' => $individualClass
      ],
      [
        'first_name' => 'Moses',
        'last_name' => 'Excel',
        'email' => 'result.workflow.moses@example.com',
        'code' => 'RWR0003',
        'classification' => $excelClass
      ],
      [
        'first_name' => 'Fatima',
        'last_name' => 'Cbt',
        'email' => 'result.workflow.fatima@example.com',
        'code' => 'RWR0004',
        'classification' => $cbtClass
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
        'other_names' => '',
        'email' => $attributes['email']
      ]);

      return $student->fresh(['user', 'classification', 'institutionUser']);
    });

    $assessments = $this->ensureAssessments($institution);
    $individualTeacher = $this->courseTeacher(
      $institution,
      $teacher,
      $mathematics,
      $individualClass
    );
    $excelTeacher = $this->courseTeacher(
      $institution,
      $teacher,
      $mathematics,
      $excelClass
    );
    $cbtTeacher = $this->courseTeacher(
      $institution,
      $teacher,
      $mathematics,
      $cbtClass
    );
    $this->courseTeacher(
      $institution,
      $teacher,
      $english,
      $individualClass
    );

    $this->writeClassSheet(
      $students->where('classification_id', $excelClass->id)->first(),
      $mathematics
    );

    $this->seedCbtExam(
      $institution,
      $academicSession,
      $classificationGroup,
      $cbtClass,
      $students->where('classification_id', $cbtClass->id)->first(),
      $mathematics,
      $cbtTeacher
    );

    $this->info('Result recording workflow fixtures ready.');
    $this->info("Institution: {$institution->name} ({$institution->uuid})");
    $this->info("Individual class: {$individualClass->title}");
    $this->info("Excel class: {$excelClass->title}");
    $this->info("CBT class: {$cbtClass->title}");
    $this->info('Assessments: ' . $assessments->pluck('title')->join(', '));
    $this->info("Individual course teacher: {$individualTeacher->id}");
    $this->info("Excel course teacher: {$excelTeacher->id}");
    $this->info('Class sheet: ' . $this->classSheetPath());

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
    SaveInstitutionSetting::run($institution, [
      'key' => InstitutionSettingType::CurrentTerm->value,
      'value' => TermType::First->value
    ]);
    SaveInstitutionSetting::run($institution, [
      'key' => InstitutionSettingType::CurrentAcademicSession->value,
      'value' => $academicSession->id
    ]);
    SaveInstitutionSetting::run($institution, [
      'key' => InstitutionSettingType::UsesMidTermResult->value,
      'value' => 1
    ]);
  }

  private function classification(
    ClassificationGroup $classificationGroup,
    string $title
  ): Classification {
    return Classification::factory()
      ->classificationGroup($classificationGroup)
      ->create([
        'title' => $title,
        'description' => 'Isolated result workflow tutorial class',
        'form_teacher_id' => null
      ]);
  }

  private function ensureAssessments(Institution $institution)
  {
    return collect([
      ['title' => 'first_assessment', 'max' => 20],
      ['title' => 'second_assessment', 'max' => 20]
    ])->map(
      fn(array $attributes) => Assessment::query()->updateOrCreate(
        [
          'institution_id' => $institution->id,
          'title' => $attributes['title']
        ],
        [
          'max' => $attributes['max'],
          'term' => TermType::First->value,
          'for_mid_term' => false,
          'description' => 'Assessment component used in the result workflow'
        ]
      )
    );
  }

  private function courseTeacher(
    Institution $institution,
    User $teacher,
    Course $course,
    Classification $classification
  ): CourseTeacher {
    return CourseTeacher::factory()->create([
      'institution_id' => $institution->id,
      'course_id' => $course->id,
      'user_id' => $teacher->id,
      'classification_id' => $classification->id
    ]);
  }

  private function writeClassSheet(
    Student $student,
    Course $course
  ): void {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
      ['ID', 'Student', $course->code],
      [$student->code, $student->user->full_name, 76]
    ]);

    $writer = new Xlsx($spreadsheet);
    $writer->save($this->classSheetPath());
  }

  private function classSheetPath(): string
  {
    return storage_path('app/tutorial-result-recording-workflow.xlsx');
  }

  private function seedCbtExam(
    Institution $institution,
    AcademicSession $academicSession,
    ClassificationGroup $classificationGroup,
    Classification $classification,
    Student $student,
    Course $course,
    CourseTeacher $courseTeacher
  ): void {
    $courseSession = CourseSession::factory()
      ->course($course)
      ->create(['session' => 'CBT Result Session']);

    $event = Event::factory()
      ->started()
      ->institution($institution)
      ->create([
        'title' => 'Result Workflow CBT Exam',
        'description' => 'Completed CBT outcome for result transfer.',
        'duration' => 45,
        'num_of_activations' => 10,
        'num_of_subjects' => 1,
        'type' => EventType::StudentTest->value,
        'classification_group_id' => $classificationGroup->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $academicSession->id,
        'term' => TermType::First->value,
        'expires_at' => now()->addDay()
      ]);

    EventCourseable::factory()
      ->event($event, $courseSession)
      ->create();

    $exam = Exam::factory()
      ->ended()
      ->event($event)
      ->examable($student)
      ->create([
        'num_of_questions' => 20,
        'score' => 16,
        'submitted_at' => now()->subHour(),
        'end_time' => now()->subHour()
      ]);

    ExamCourseable::factory()
      ->exam($exam)
      ->courseable($courseSession)
      ->create([
        'score' => 16,
        'num_of_questions' => 20
      ]);

    // Keep the teacher assignment explicit for the transfer form's selector.
    $courseTeacher->refresh();
  }
}
