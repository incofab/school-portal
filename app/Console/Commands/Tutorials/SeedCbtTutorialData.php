<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\EnsureTutorialInstitution;
use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedCbtTutorialData extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'tutorial:seed-cbt-demo';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create the deterministic class group, student, and subject fixtures used to generate the CBT exam tutorial video';

  const DEMO_CLASS_GROUP_TITLE = 'CBT Demo Group';

  const DEMO_CLASS_TITLE = 'JSS 3';

  const DEMO_STUDENT_CODE = 'TUT0002';

  const DEMO_STUDENT_EMAIL = 'tutorial.cbt.student@example.com';

  const DEMO_COURSE_TITLE = 'CBT Demo Subject';

  const DEMO_COURSE_CODE = 'CBTDEMO';

  public function handle(): int
  {
    [, $institution] = EnsureTutorialInstitution::run();

    // The event's "Class Group" selector (see create-edit-event.tsx) only
    // ever targets a ClassificationGroup, not an individual class, so the
    // demo class needs to belong to one for the event to be visible to the
    // demo student (see Event::scopeForStudent).
    $classificationGroup = ClassificationGroup::firstOrCreate(
      [
        'institution_id' => $institution->id,
        'title' => self::DEMO_CLASS_GROUP_TITLE
      ],
      ClassificationGroup::titleFallbacks()
    );

    $classification = Classification::firstOrCreate(
      ['institution_id' => $institution->id, 'title' => self::DEMO_CLASS_TITLE],
      [
        'classification_group_id' => $classificationGroup->id,
        'description' => 'Demo class used by the CBT exam tutorial'
      ]
    );
    if ($classification->classification_group_id !== $classificationGroup->id) {
      $classification
        ->fill(['classification_group_id' => $classificationGroup->id])
        ->save();
    }

    $studentUser = User::withTrashed()->updateOrCreate(
      ['email' => self::DEMO_STUDENT_EMAIL],
      [
        'first_name' => 'Tutorial',
        'last_name' => 'CbtStudent',
        'other_names' => '',
        'phone' => '08055555555',
        'email_verified_at' => now(),
        'password' => Hash::make(config('tutorial.demo_password')),
        'deleted_at' => null
      ]
    );

    $studentInstitutionUser = InstitutionUser::firstOrCreate(
      ['institution_id' => $institution->id, 'user_id' => $studentUser->id],
      ['role' => InstitutionUserType::Student]
    );

    $student = Student::withTrashed()->updateOrCreate(
      ['code' => self::DEMO_STUDENT_CODE],
      [
        'institution_user_id' => $studentInstitutionUser->id,
        'user_id' => $studentUser->id,
        'classification_id' => $classification->id,
        'guardian_phone' => '08044444444',
        'deleted_at' => null
      ]
    );

    Course::firstOrCreate(
      [
        'institution_id' => $institution->id,
        'title' => self::DEMO_COURSE_TITLE
      ],
      ['code' => self::DEMO_COURSE_CODE, 'order' => 0]
    );

    $this->info(
      "Class ready: {$classification->title} (group: {$classificationGroup->title})"
    );
    $this->info("Student ready: {$student->code}");
    $this->info('Subject ready: ' . self::DEMO_COURSE_TITLE);

    return self::SUCCESS;
  }
}
