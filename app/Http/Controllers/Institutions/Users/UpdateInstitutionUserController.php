<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Storage;

class UpdateInstitutionUserController extends Controller
{
  public function edit(Request $request, Institution $institution, User $user)
  {
    $this->validateUser($user);
    return inertia('institutions/users/profile', [
      'user' => $user,
      'student' => $user->institutionStudent()
    ]);
  }

  public function update(Request $request, Institution $institution, User $user)
  {
    $data = $request->validate(User::generalRule($user->id), $request->all());
    $this->validateUser($user);

    $user->fill($data)->save();

    return response()->json(['user' => $user]);
  }

  public function uploadPhoto(
    Request $request,
    Institution $institution,
    User $user
  ) {
    $this->validateUser($user);
    $request->validate([
      'photo' => ['required', 'image', 'mimes:jpg,png,jpeg', 'max:2048']
    ]);
    $imagePath = $request->photo->store('avatars/users', 's3_public');
    $publicUrl = Storage::disk('s3_public')->url($imagePath);

    $user->fill(['photo' => $publicUrl])->save();

    return response()->json([
      'url' => $publicUrl
    ]);
  }

  private function validateUser(User $user)
  {
    $currentUser = currentUser();
    if ($user->is($currentUser)) {
      return;
    }

    $currentInstitutionUser = currentInstitutionUser();
    abort_unless(
      $currentInstitutionUser->isAdmin(),
      403,
      'You are not authorize to update another person\'s profile'
    );
    $institutionUser = $user->institutionUser()->first();
    abort_unless(
      $currentInstitutionUser->institution_id ===
        $institutionUser->institution_id,
      403,
      'This user is not part of your institution'
    );
  }
}
