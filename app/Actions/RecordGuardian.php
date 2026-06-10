<?php

namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\GuardianStudent;
use App\Models\User;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\Audit\ModelAudit;
use Illuminate\Support\Facades\DB;

class RecordGuardian
{
  public function __construct(private array $userData)
  {
  }

  public static function make(array $userData)
  {
    return new self($userData);
  }

  public function create(int $studentId)
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->create([
      ...collect($this->userData)
        ->except('relationship')
        ->toArray(),
      'password' => bcrypt('password')
    ]);

    $this->syncRole($user);
    // $user->guardianStudents()->firstOrCreate(
    //   [
    //     'institution_id' => currentInstitution()->id,
    //     'student_id' => $studentId
    //   ],
    //   collect($this->userData)
    //     ->only('relationship')
    //     ->toArray()
    // );
    $guardianStudent = self::attachStudent(
      $user,
      $studentId,
      $this->userData['relationship']
    );
    app(AcademicActivityLogger::class)->guardianRecorded($guardianStudent);

    DB::commit();
  }

  public static function attachStudent(
    User $guardianUser,
    int $studentId,
    string $relationship
  ): GuardianStudent {
    $guardianStudent = ModelAudit::withoutAuditingFor(
      GuardianStudent::class,
      fn() => $guardianUser->guardianStudents()->firstOrCreate(
        [
          'institution_id' => currentInstitution()->id,
          'student_id' => $studentId
        ],
        ['relationship' => $relationship]
      )
    );

    if ($guardianStudent->wasRecentlyCreated) {
      app(AcademicActivityLogger::class)->guardianAssigned($guardianStudent);
    }

    return $guardianStudent;
  }

  public function update(User $user)
  {
    DB::beginTransaction();
    $user
      ->fill(
        collect($this->userData)
          ->except('role')
          ->toArray()
      )
      ->save();
    DB::commit();
  }

  public function syncRole(User $user)
  {
    $user
      ->institutions()
      ->syncWithPivotValues(
        [currentInstitution()->id],
        ['role' => InstitutionUserType::Guardian]
      );
  }
}
