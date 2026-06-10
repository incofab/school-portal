<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Actions\RecordStaff;
use App\Enums\InstitutionUserStatus;
use App\Enums\Media\MediaVisibility;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStaffRequest;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\SecurityActivityLogger;
use App\Support\Media\MediaManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

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

  public function edit(
    Institution $institution,
    InstitutionUser $editInstitutionUser
  ) {
    $editInstitutionUser->load(['user', 'institution']);
    $this->validateUser($editInstitutionUser->user);

    return inertia('institutions/users/create-edit-user', [
      'institutionUser' => $editInstitutionUser
    ]);
  }

  // Mainly for staff, students are editted elsewhere
  public function update(
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
      'photo' => ['required', 'image', 'mimes:jpg,png,jpeg,webp', 'max:2048']
    ]);
    $res = app(MediaManager::class)->storeUploadedFile(
      $request->file('photo'),
      $user,
      'profile_photo',
      S3Folder::UserAvartars->value,
      $institution,
      currentUser(),
      visibility: MediaVisibility::Public,
      legacyUrlColumn: 'photo'
    );

    return response()->json([
      'url' => $res->media?->url
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
        $institutionUser?->institution_id,
      403,
      'This user is not part of your institution'
    );
  }

  public function updateStatus(
    Request $request,
    Institution $institution,
    InstitutionUser $institutionUser
  ) {
    $request->validate([
      'status' => ['required', new Enum(InstitutionUserStatus::class)],
      'status_message' => ['nullable', 'string', 'max:255']
    ]);
    $status = $request->status;
    $oldStatus = $institutionUser->status?->value;

    abort_if(
      $status === InstitutionUserStatus::Suspended->value &&
        currentInstitutionUser()->id === $institutionUser->id,
      403,
      'You cannot suspend yourself'
    );

    ModelAudit::withoutAuditingFor(InstitutionUser::class, function () use (
      $institutionUser,
      $status,
      $request
    ) {
      $institutionUser
        ->fill([
          'status' => $status,
          'status_message' => $request->status_message
        ])
        ->save();
    });

    $institutionUser->loadMissing('user');

    app(SecurityActivityLogger::class)->userStatusChanged(
      currentUser(),
      $institutionUser,
      $institution,
      $oldStatus,
      $status,
      $request->status_message
    );

    return $this->ok();
  }
}
