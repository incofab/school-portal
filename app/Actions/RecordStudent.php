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
  // private Institution $institution;
  public function __construct(
    private Institution $institution,
    private array $data
  ) {
    $this->userData = collect($data)
      ->except('classification_id', 'role', 'guardian_phone', 'code')
      ->toArray();
  }

  public static function make(Institution $institution, array $data)
  {
    return new self($institution, $data);
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
        'code' => $this->data['code'] ?? Student::generateStudentID(),
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
