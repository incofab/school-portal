<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ChangeUserRoleController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    InstitutionUser $suppliedInstitutionUser
  ) {
    $data = $request->validate([
      'role' => ['required', new Enum(InstitutionUserType::class)]
    ]);

    abort_unless(currentUser()->isInstitutionAdmin(), 403);

    $role = $data['role'];
    $prevRole = $suppliedInstitutionUser->role->value;
    $this->canChangeRole($prevRole, $role);

    $suppliedInstitutionUser->fill(['role' => $role])->update();

    return $this->ok();
  }

  function canChangeRole($prevRole, $newRole)
  {
    if ($prevRole === InstitutionUserType::Student->value) {
      abort_unless(
        $newRole === InstitutionUserType::Alumni->value,
        403,
        'You cannot change a student to another role aside alumni'
      );
    }
    if ($prevRole === InstitutionUserType::Alumni->value) {
      abort_unless(
        $newRole === InstitutionUserType::Student->value,
        403,
        'You cannot change an alumni to another role aside student'
      );
    }
    if (
      in_array($newRole, [
        InstitutionUserType::Student->value,
        InstitutionUserType::Alumni->value
      ])
    ) {
      abort_unless(
        in_array($prevRole, [
          InstitutionUserType::Student->value,
          InstitutionUserType::Alumni->value
        ]),
        403,
        'You can only change student and alumni with each other'
      );
    }
  }
}
