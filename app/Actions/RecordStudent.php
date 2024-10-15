<?php

namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStudent
{
  private $userData = [];
  private Institution $institution;
  public function __construct(private array $data)
  {
    $this->institution = currentInstitution();
    $this->userData = collect($data)
      ->except('classification_id', 'role', 'guardian_phone')
      ->toArray();
  }

  public static function make(array $data)
  {
    return new self($data);
  }

  public function create(): Student
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->create([
      ...$this->userData,
      'password' => bcrypt('password')
    ]);

    $student = $this->attach($user);

    DB::commit();
    return $student;
  }

  private function attach(User $user)
  {
    $institutionUser = $user->institutionUsers()->firstOrCreate(
      [
        'institution_id' => $this->institution->id
      ],
      ['role' => InstitutionUserType::Student]
    );

    $student = $this->createUpdateStudent(
      $user,
      [
        'institution_user_id' => $institutionUser->id,
        'code' => Student::generateStudentID(),
        ...collect($this->data)
          ->only('classification_id', 'guardian_phone')
          ->toArray()
      ],
      $institutionUser
    );
    return $student;
  }

  function update(Student $student)
  {
    $student->load('user', 'institutionUser');
    $user = $student->user;
    $institutionUser = $student->institutionUser;

    $user->fill($this->userData)->save();
    $this->createUpdateStudent(
      $user,
      collect($this->data)
        ->only(['guardian_phone'])
        ->toArray(),
      $institutionUser
    );
  }

  private function createUpdateStudent(
    User $user,
    $data,
    InstitutionUser $institutionUser
  ) {
    return $user
      ->student()
      ->updateOrCreate(['institution_user_id' => $institutionUser->id], $data);
  }
}

/**
 * What is the essence of 'guardian_phone' here?
 * Does it imply that the 'guardian' record/user should be created first?
 * Does this also take care of the 'institution_users' DB table?
 * Do I need to execute 'RecordGuardian' seperately in order to take care of 'guardian_students' DB table?
 * What is this 'Actions' folder? and How is it different from Helpers?
 */
