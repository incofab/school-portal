<?php

namespace App\Http\Controllers\Users;

use App\Enums\Gender;
use App\Enums\Media\MediaVisibility;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit\ModelAudit;
use App\Support\Media\MediaManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class ProfileController extends Controller
{
  public function show()
  {
    $user = currentUser()?->load(
      'roles',
      'partnerUser.partner',
      'institutionUsers.institution',
      'institutionUsers.student.classification'
    );

    return Inertia::render('users/profile', [
      'user' => $user,
      'institutionUser' =>
        currentInstitutionUser() ?? $user?->institutionUsers->first()
    ]);
  }

  public function update(Request $request)
  {
    $user = currentUser();
    $validated = $request->validate([
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'other_names' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:20'],
      'gender' => ['nullable', new Enum(Gender::class)],
      'email' => [
        'required',
        'string',
        'email',
        Rule::unique('users', 'email')->ignore($user?->id)
      ]
    ]);

    ModelAudit::withoutAuditingFor(User::class, function () use (
      $user,
      $validated
    ) {
      $user?->fill($validated)->save();
    });

    return response()->json([
      'message' => 'Profile updated successfully',
      'user' => $user?->fresh()
    ]);
  }

  public function uploadPhoto(Request $request)
  {
    $request->validate([
      'photo' => ['required', 'image', 'mimes:jpg,png,jpeg,webp', 'max:2048']
    ]);

    $user = currentUser();
    $res = app(MediaManager::class)->storeUploadedFile(
      $request->file('photo'),
      $user,
      'profile_photo',
      S3Folder::UserAvartars->value,
      currentInstitution(),
      $user,
      visibility: MediaVisibility::Public,
      legacyUrlColumn: 'photo'
    );

    return response()->json([
      'url' => $res->media?->url
    ]);
  }
}
