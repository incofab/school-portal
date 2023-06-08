<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStudent
{
  private $userData = [];
  public function __construct(
    private array $data,
    private Classification $classification
  ) {
    $this->userData = collect($data)
      ->except('classification_id', 'role', 'guardian_phone')
      ->toArray();
  }

  public static function make(array $data, Classification $classification)
  {
    return new self($data, $classification);
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

  public function attach(User $user)
  {
    $institutionUser = $user->institutionUsers()->firstOrCreate(
      [
        'institution_id' => $this->classification->institution_id
      ],
      ['role' => InstitutionUserType::Student]
    );

    $this->createUpdateStudent($user, [
      'institution_user_id' => $institutionUser->id,
      'classification_id' => $this->classification->id,
      'code' => Student::generateStudentID(),
      'guardian_phone' => $this->data['guardian_phone'] ?? null
    ]);
  }

  function update(User $user)
  {
    $user->fill($this->userData)->save();
    $this->createUpdateStudent(
      $user,
      collect($this->data)
        ->only(['classification_id', 'guardian_phone'])
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
