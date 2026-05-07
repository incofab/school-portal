<?php

namespace App\Http\Controllers\Institutions;

use App\Actions\Users\InstitutionDashboardStat;
use App\Enums\InstitutionUserType;
use App\Enums\Media\MediaVisibility;
use App\Enums\Payments\PaymentStatus;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\ReservedAccount;
use App\Support\Media\MediaManager;
use App\Support\SetupChecklistHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    public function index(Institution $institution, Request $request)
    {
        $request->validate([
            'refresh' => ['sometimes', 'boolean'],
        ]);
        $isSetupComplete = SetupChecklistHandler::make(
            $institution
        )->isSetupComplete();

        $institutionGroup = currentInstitution()->institutionGroup;
        $currentInstitutionUser = currentInstitutionUser();
        $showAttentionSection = in_array($currentInstitutionUser->role, [
            InstitutionUserType::Admin,
            InstitutionUserType::Accountant,
        ]);

        return inertia('institutions/dashboard', [
            'institutionGroup' => $institutionGroup,
            'isSetupComplete' => $isSetupComplete,
            'reservedAccounts' => currentInstitutionUser()->isGuardian()
              ? ReservedAccount::getReservedAccounts(currentUser(), true)
              : [],
            'attentionSummary' => $showAttentionSection
              ? [
                  'pendingManualPaymentsCount' => ManualPayment::query()
                      ->where('institution_id', $institution->id)
                      ->where('status', PaymentStatus::Pending)
                      ->count(),
                  'unreadChatCount' => ChatThread::unreadCountFor(
                      $institution,
                      currentUser(),
                      $currentInstitutionUser
                  ),
                  'hasBankAccounts' => $institutionGroup->bankAccounts()->exists(),
                  'canManageBankAccounts' => $currentInstitutionUser->isAdmin(),
              ]
              : null,
            'dashboardData' => InstitutionDashboardStat::make(
                $institution,
                $currentInstitutionUser
            )->getStat($request->refresh),
        ]);
    }

    public function setupChecklist(Request $request, Institution $institution)
    {
        $todos = SetupChecklistHandler::make($institution)->getChecklist();

        return Inertia::render('institutions/todo-list/list-todo-list', [
            'todos' => $todos,
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
            'institution' => $institution,
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
                'website' => ['nullable', 'string'],
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
            'photo' => ['required', 'image', 'mimes:jpg,png,jpeg,webp', 'max:2048'],
        ]);
        $media = app(MediaManager::class)->storeUploadedFile(
            $request->file('photo'),
            $institution,
            'profile_photo',
            $institution->folder(S3Folder::Base),
            $institution,
            currentUser(),
            visibility: MediaVisibility::Public,
            legacyUrlColumn: 'photo'
        );

        return response()->json(['url' => $media->url]);
    }
}
