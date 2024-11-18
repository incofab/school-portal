<?php

namespace App\Http\Controllers;

use App\Actions\RecordStaff;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use App\Support\Res;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class AttachInstitutionUserController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    $data = $request->validate([
      'role' => ['required', new Enum(InstitutionUserType::class)],
      'email' => ['required', Rule::exists('users', 'email')]
    ]);

    $user = User::where('email', $data['email'])->first();
    $res = $this->verifyUser($user, $institution);

    if ($res->isNotSuccessful()) {
      throw ValidationException::withMessages([
        'email' => $res->message
      ]);
    }

    RecordStaff::make($institution, $data)->syncRole($user);

    return $this->message('User has been attached successfully');
  }

  private function verifyUser(User $user, Institution $currentInstitution): Res
  {
    $institutions = $user->institutions()->get();
    if ($institutions->isEmpty()) {
      return successRes();
    }

    if (
      $currentInstitution->institution_group_id ===
      $institutions->first()->institution_group_id
    ) {
      return successRes();
    }

    return failRes('This user belongs to another institution group');
  }
}
