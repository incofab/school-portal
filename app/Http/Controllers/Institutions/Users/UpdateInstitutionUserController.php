<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Actions\RecordStaff;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStaffRequest;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Http\Request;
use Storage;

class UpdateInstitutionUserController extends Controller
{
  public function profile(
    Request $request,
    Institution $institution,
    User $user
  ) {
    /** Permit Guardian to view the profile of their dependants. */
    if (
      !currentInstitutionUser()->isGuardian() ||
      !in_array(
        $user->id,
        currentUser()
          ->dependents->pluck('user_id')
          ->toArray()
      )
    ) {
      $this->validateUser($user);
    }

    $institutionUser = $user
      ->institutionUser()
      ->with('student.classification')
      ->first();
    return inertia('institutions/users/profile', [
      'user' => $user,
      'institutionUser' => $institutionUser,
      'student' => $institutionUser?->student
    ]);
  }

  function edit(Institution $institution, InstitutionUser $editInstitutionUser)
  {
    $editInstitutionUser->load(['user', 'institution']);
    $this->validateUser($editInstitutionUser->user);
    return inertia('institutions/users/create-edit-user', [
      'institutionUser' => $editInstitutionUser
    ]);
  }

  // Mainly for staff, students are editted elsewhere
  function update(
    CreateStaffRequest $request,
    Institution $institution,
    InstitutionUser $editInstitutionUser
  ) {
    $this->validateUser($editInstitutionUser->user);
    RecordStaff::make($institution, $request->validated())->update(
      $editInstitutionUser->user
    );
    return $this->ok();
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
    $imagePath = $request->photo->store(
      S3Folder::UserAvartars->value,
      's3_public'
    );
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
