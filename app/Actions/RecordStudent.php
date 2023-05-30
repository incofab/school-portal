<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Http\Requests\CreateStudentRequest;
use App\Models\Classification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordStudent
{
  public static function create(CreateStudentRequest $request)
  {
    DB::beginTransaction();

    /** @var User $user */
    $user = User::query()->updateOrCreate(
      ['email' => $request->email],
      [
        ...collect($request->validated())->except(
          'classification_id',
          'role',
          'guardian_phone'
        ),
        'password' => bcrypt('password')
      ]
    );

    static::attach($request, $user, $request->classification);

    DB::commit();
  }

  public static function attach(
    CreateStudentRequest $request,
    User $user,
    Classification $classification
  ) {
    if ($user->institutionUser()->exists()) {
      return;
    }
    $user
      ->institutions()
      ->syncWithPivotValues(
        [$classification->institution_id],
        ['role' => InstitutionUserType::Student]
      );

    $user->student()->firstOrCreate([
      'classification_id' => $classification->id,
      'code' => Student::generateStudentID(),
      'guardian_phone' => $request->guardian_phone
    ]);
  }
}
