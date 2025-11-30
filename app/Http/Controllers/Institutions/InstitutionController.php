<?php

namespace App\Http\Controllers\Institutions;

use App\Actions\Users\InstitutionDashboardStat;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\ReservedAccount;
use App\Support\SetupChecklistHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Storage;

class InstitutionController extends Controller
{
  function index(Institution $institution, Request $request)
  {
    $request->validate([
      'refresh' => ['sometimes', 'boolean']
    ]);
    $isSetupComplete = SetupChecklistHandler::make(
      $institution
    )->isSetupComplete();

    $institutionGroup = currentInstitution()->institutionGroup;

    return inertia('institutions/dashboard', [
      'institutionGroup' => $institutionGroup,
      'isSetupComplete' => $isSetupComplete,
      'reservedAccounts' => currentInstitutionUser()->isGuardian()
        ? ReservedAccount::getReservedAccounts(currentUser(), true)
        : [],
      'dashboardData' => InstitutionDashboardStat::make(
        $institution,
        currentInstitutionUser()
      )->getStat($request->refresh)
    ]);
  }

  function setupChecklist(Request $request, Institution $institution)
  {
    $todos = SetupChecklistHandler::make($institution)->getChecklist();

    return Inertia::render('institutions/todo-list/list-todo-list', [
      'todos' => $todos
    ]);
  }

  public function profile(Request $request, Institution $institution)
  {
    abort_unless(
      currentUser()->isInstitutionAdmin(),
      403,
      'View Profile: Access denied'
    );

    return inertia('institutions/institution-profile', [
      'institution' => $institution
    ]);
  }

  public function update(Request $request, Institution $institution)
  {
    abort_unless(
      currentUser()->isInstitutionAdmin(),
      403,
      'Update Profile: Access denied'
    );

    $data = $request->validate(
      [
        'name' => ['required', 'string'],
        'subtitle' => ['nullable', 'string'],
        'caption' => ['nullable', 'string'],
        'phone' => ['nullable', 'string'],
        'email' => ['nullable', 'string'],
        'address' => ['nullable', 'string'],
        'website' => ['nullable', 'string']
      ],
      $request->all()
    );

    $institution->fill($data)->save();

    return response()->json(['institution' => $institution]);
  }

  public function uploadPhoto(Request $request, Institution $institution)
  {
    abort_unless(
      currentUser()->isInstitutionAdmin(),
      403,
      'Upload Photo: Access denied'
    );
    $request->validate([
      'photo' => ['required', 'image', 'mimes:jpg,png,jpeg,webp', 'max:2048']
    ]);
    $imagePath = $request->photo->store(
      $institution->folder(S3Folder::Base),
      's3_public'
    );
    $publicUrl = Storage::disk('s3_public')->url($imagePath);

    $institution->fill(['photo' => $publicUrl])->save();

    return response()->json([
      'url' => $publicUrl
    ]);
  }
}
