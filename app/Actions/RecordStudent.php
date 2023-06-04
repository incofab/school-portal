<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStudent
{
  public static function create(array $data, Classification $classification)
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->updateOrCreate(
      ['email' => $data['email']],
      [
        ...collect($data)->except(
          'classification_id',
          'role',
          'guardian_phone'
        ),
        'password' => bcrypt('password')
      ]
    );

    static::attach($data, $user, $classification);

    DB::commit();
  }

  public static function attach(
    $data,
    User $user,
    Classification $classification
  ) {
    if ($user->institutionUser()->exists()) {
      return;
    }
    $institutionUser = $user->institutionUsers()->firstOrCreate(
      [
        'institution_id' => $classification->institution_id
      ],
      ['role' => InstitutionUserType::Student]
    );

    $user->student()->firstOrCreate([
      'institution_user_id' => $institutionUser->id,
      'classification_id' => $classification->id,
      'code' => Student::generateStudentID(),
      'guardian_phone' => $data['guardian_phone'] ?? null
    ]);
  }
}
