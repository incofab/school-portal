<?php

namespace Database\Seeders;

use App\Actions\CourseResult\ClassResultInfoAction;
use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Actions\SeedSetupData;
use App\Enums\TermType;
use App\Models\Institution;
use Illuminate\Support\Arr;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use App\Models\InstitutionGroup;
use App\Enums\InstitutionSettingType;
use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\InstitutionSetting;
use App\Models\InstitutionUser;
use App\Models\PriceList;
use App\Models\Student;
use App\Models\TermResult;
use App\Support\SettingsHandler;

class MyRefillDatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // This will never run in production
    if (!config('app.debug')) {
      return;
    }

    $this->seed();
  }

  function seed()
  {
    if (InstitutionGroup::count() > 0) {
      return;
    }
    $institutionGroup = InstitutionGroup::factory()->create([
      'name' => 'Success Academy Group'
    ]);
    $institution = Institution::factory()
      ->for($institutionGroup)
      ->create(['name' => 'Success Academy']);
    $institutionAdmin = $institution->createdBy;
    $institutionAdmin->fill(['email' => 'success@email.com'])->save();

    SeedSetupData::run($institution);

    $this->createInstitutionSetting($institution);

    $this->createClasses($institution);
    $this->recordSubjects($institution);
    $this->createStudents($institution, 10);
    $this->createExamResult($institution);

    $this->createPriceList($institutionGroup);
  }

  function createInstitutionSetting(Institution $institution)
  {
    $acadSessions = AcademicSession::all()
      ->pluck('id')
      ->toArray();

    $settingData = [
      [
        'key' => InstitutionSettingType::CurrentTerm->value,
        'value' => Arr::random([
          TermType::First,
          TermType::Second,
          TermType::Third
        ]),
        'institution_id' => $institution->id
      ],
      [
        'key' => InstitutionSettingType::CurrentAcademicSession->value,
        'value' => Arr::random($acadSessions),
        'institution_id' => $institution->id
      ]
    ];

    InstitutionSetting::insert($settingData);
  }

  function createClasses(Institution $institution)
  {
    foreach (['JSS 1', 'JSS 2', 'JSS 3'] as $key => $value) {
      $classificationGroup = ClassificationGroup::factory()
        ->withInstitution($institution)
        ->create(['title' => $value]);
      Classification::factory()
        ->classificationGroup($classificationGroup)
        ->create(['title' => $value]);
    }
  }

  function createStudents(Institution $institution, $numPerClass)
  {
    $classes = $institution->classifications()->get();
    foreach ($classes as $key => $class) {
      Student::factory($numPerClass)
        ->withInstitution($institution, $class)
        ->create();
    }
  }

  function createPriceList(InstitutionGroup $institutionGroup)
  {
    $priceData = [
      'type' => PriceType::ResultChecking->value,
      'institution_group_id' => $institutionGroup->id,
      'payment_structure' => Arr::random([
        PaymentStructure::PerTerm->value,
        PaymentStructure::PerSession->value,
        PaymentStructure::PerStudentPerTerm->value,
        PaymentStructure::PerStudentPerSession->value
      ]),
      'amount' => rand(300, 20000)
    ];

    PriceList::query()->updateOrCreate(
      collect($priceData)
        ->only('type', 'institution_group_id')
        ->toArray(),
      $priceData
    );
  }

  function recordSubjects(Institution $institution)
  {
    $courseTitles = [
      'Mathematics',
      'Economics',
      'English',
      'Biology',
      'Chemistry',
      'Physics',
      'Agriculture',
      'History',
      'Geography',
      'Commerce',
      'Government'
    ];

    foreach ($courseTitles as $key => $title) {
      Course::query()->firstOrCreate(
        ['title' => $title, 'institution_id' => $institution->id],
        ['code' => $title]
      );
    }

    $teacher = InstitutionUser::factory()
      ->teacher($institution)
      ->create()->user;
    $teacher
      ->fill([
        'email' => 'teacher@email.com',
        'first_name' => 'Teacher1',
        'last_name' => 'Teacher1'
      ])
      ->save();
    $classes = $institution->classifications()->get();
    $courses = $institution->courses()->get();
    foreach ($classes as $key => $class) {
      foreach ($courses as $key => $course) {
        CourseTeacher::query()->firstOrCreate([
          'institution_id' => $institution->id,
          'classification_id' => $class->id,
          'course_id' => $course->id,
          'user_id' => $teacher->id
        ]);
      }
    }
  }

  private function createExamResult(Institution $institution)
  {
    $courses = $institution->courses()->get();
    $settingsHandler = SettingsHandler::makeFromInstitution($institution);
    $classifications = $institution->classifications()->get();
    $academicSessionId = $settingsHandler->getCurrentAcademicSession();
    $currentTerm = $settingsHandler->getCurrentTerm();
    $forMidTerm = false;
    foreach ($classifications as $key => $classification) {
      $students = $classification->students()->get();
      foreach ($courses as $key => $course) {
        foreach ($students as $key => $student) {
          $courseTeacher =
            CourseTeacher::where([
              'institution_id' => $institution->id,
              'course_id' => $course->id,
              'classification_id' => $classification->id
            ])->first() ?? CourseTeacher::first();
          abort_unless($courseTeacher, 'No course teacher found');
          CourseResult::factory()
            ->withInstitution($institution)
            ->create([
              'academic_session_id' => $academicSessionId,
              'term' => $currentTerm,
              'for_mid_term' => $forMidTerm,
              'classification_id' => $classification->id,
              'course_id' => $course->id,
              'student_id' => $student->id,
              'teacher_user_id' => $courseTeacher->user_id
            ]);
        }
        EvaluateCourseResultForClass::run(
          classification: $classification,
          courseId: $course->id,
          academicSessionId: $academicSessionId,
          term: $currentTerm,
          forMidTerm: $forMidTerm
        );
      }

      ClassResultInfoAction::make()->calculate(
        classification: $classification,
        academicSessionId: $academicSessionId,
        term: $currentTerm,
        forMidTerm: $forMidTerm,
        forceCalculateTermResult: true
      );
    }
  }
}
