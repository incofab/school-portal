<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\EnsureTutorialInstitution;
use App\Actions\Tutorials\ResetFeeTutorialData;
use App\Enums\GuardianRelationship;
use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\GuardianStudent;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedFeeTutorialData extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'tutorial:seed-fee-demo';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create/reset the deterministic class, student, guardian, and fee state used to generate the fee recording & payment tutorial video';

  const DEMO_CLASS_TITLE = 'JSS 1';

  const DEMO_STUDENT_CODE = 'TUT0001';

  const DEMO_STUDENT_EMAIL = 'tutorial.student@example.com';

  const DEMO_GUARDIAN_EMAIL = 'tutorial.guardian@example.com';

  public function handle(): int
  {
    [, $institution] = EnsureTutorialInstitution::run();

    $classification = Classification::firstOrCreate(
      ['institution_id' => $institution->id, 'title' => self::DEMO_CLASS_TITLE],
      ['description' => 'Demo class used by the fee tutorial']
    );

    $studentUser = User::withTrashed()->updateOrCreate(
      ['email' => self::DEMO_STUDENT_EMAIL],
      [
        'first_name' => 'Tutorial',
        'last_name' => 'Student',
        'other_names' => '',
        'phone' => '08088888888',
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
        'guardian_phone' => '08077777777',
        'deleted_at' => null
      ]
    );

    $guardianUser = User::withTrashed()->updateOrCreate(
      ['email' => self::DEMO_GUARDIAN_EMAIL],
      [
        'first_name' => 'Tutorial',
        'last_name' => 'Guardian',
        'other_names' => '',
        'phone' => '08066666666',
        'email_verified_at' => now(),
        'password' => Hash::make(config('tutorial.demo_password')),
        'deleted_at' => null
      ]
    );

    InstitutionUser::firstOrCreate(
      ['institution_id' => $institution->id, 'user_id' => $guardianUser->id],
      ['role' => InstitutionUserType::Guardian]
    );

    GuardianStudent::firstOrCreate(
      [
        'institution_id' => $institution->id,
        'guardian_user_id' => $guardianUser->id,
        'student_id' => $student->id
      ],
      ['relationship' => GuardianRelationship::Parent]
    );

    // Reset state so re-running the tutorial produces a clean recording
    // instead of accumulating fees/payments/accounts every regeneration.
    // Scoped entirely to this dedicated demo institution, so it never
    // touches real school data. Shared with `tutorial:clear-fee-demo`,
    // which runs this same reset as a teardown after each recording.
    ResetFeeTutorialData::run($institution);

    $this->info("Class ready: {$classification->title}");
    $this->info(
      "Student ready: {$student->code} (log in at /student/login with this Student Id)"
    );
    $this->info(
      "Guardian ready: {$guardianUser->email} (log in at /login with this email)"
    );
    $this->info(
      'Bank accounts, payments, and the demo fee were reset for a clean demo.'
    );

    return self::SUCCESS;
  }
}
