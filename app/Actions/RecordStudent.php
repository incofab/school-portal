<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
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

  public function create()
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->create([
      ...$this->userData,
      'password' => bcrypt('password')
    ]);

    $this->attach($user);

    DB::commit();
  }

  private function attach(User $user)
  {
    $institutionUser = $user->institutionUsers()->firstOrCreate(
      [
        'institution_id' => $this->institution->id
      ],
      ['role' => InstitutionUserType::Student]
    );

    $this->createUpdateStudent($user, [
      'institution_user_id' => $institutionUser->id,
      'code' => Student::generateStudentID(),
      ...collect($this->data)
        ->only('classification_id', 'guardian_phone')
        ->toArray()
    ]);
  }

  function update(User $user)
  {
    $user->fill($this->userData)->save();
    $this->createUpdateStudent(
      $user,
      collect($this->data)
        ->only(['guardian_phone'])
        ->toArray()
    );
  }

  private function createUpdateStudent(User $user, $data)
  {
    $user
      ->student()
      ->updateOrCreate(['institution_user_id' => $user->id], $data);
  }
}
