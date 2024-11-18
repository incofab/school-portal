<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordGuardian
{
  function __construct(private array $userData)
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
    self::attachStudent($user, $studentId, $this->userData['relationship']);

    DB::commit();
  }

  static function attachStudent(
    User $guardianUser,
    int $studentId,
    string $relationship
  ) {
    $guardianUser->guardianStudents()->firstOrCreate(
      [
        'institution_id' => currentInstitution()->id,
        'student_id' => $studentId
      ],
      ['relationship' => $relationship]
    );
  }

  function update(User $user)
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

  function syncRole(User $user)
  {
    $user
      ->institutions()
      ->syncWithPivotValues(
        [currentInstitution()->id],
        ['role' => InstitutionUserType::Guardian]
      );
  }
}
