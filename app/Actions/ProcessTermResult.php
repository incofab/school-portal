<?php
namespace App\Actions;

use App\Enums\UserRoleType;
use App\Http\Requests\CreateStudentRequest;
use App\Models\Classification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcessTermResult
{
  public function __construct()
  {
  }

  // public static function create(CreateStudentRequest $request)
  // {
  //   DB::beginTransaction();

  //   /** @var User $user */
  //   $user = User::create([
  //     ...$request->except('classification_id'),
  //     'password' => bcrypt('password')
  //   ]);

  //   static::attach($user, $request->classification);

  //   DB::commit();
  // }

  // public static function attach(User $user, Classification $classification)
  // {
  //   $user
  //     ->institutions()
  //     ->syncWithPivotValues(
  //       [$classification->institution_id],
  //       ['role' => UserRoleType::Student]
  //     );

  //   $user->student()->firstOrCreate([
  //     'classification_id' => $classification->id,
  //     'code' => Student::generateStudentID()
  //   ]);
  // }
}
