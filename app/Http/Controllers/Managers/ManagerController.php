<?php
namespace App\Http\Controllers\Managers;

use App\Actions\RecordUsers\RecordPartner;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
  function dashboard(Request $request)
  {
    $user = currentUser();
    $partner = $user->isPartner() ? $user->partner : null;
    $partnerUserIds = $partner
      ? $partner->partnerUsers()->pluck('user_id')
      : collect();

    $commissionBalance = $partner?->wallet ?? 0;
    $attentionSummary = $user->isAdmin()
      ? [
        'pendingWithdrawalsCount' => Withdrawal::query()
          ->where('status', WithdrawalStatus::Pending->value)
          ->count()
      ]
      : null;
    $partnerAnalytics = $partner
      ? [
        'institutionGroupsCount' => InstitutionGroup::query()
          ->whereIn('partner_user_id', $partnerUserIds)
          ->count(),
        'institutionsCount' => Institution::query()
          ->whereHas(
            'institutionGroup',
            fn($query) => $query->whereIn('partner_user_id', $partnerUserIds)
          )
          ->count(),
        'registrationRequestsCount' => RegistrationRequest::query()
          ->whereIn('partner_user_id', $partnerUserIds)
          ->count(),
        'partnerUsersCount' => $partnerUserIds->count(),
        'bankAccountsCount' => $partner->bankAccounts()->count(),
        'pendingWithdrawalsCount' => $partner
          ->withdrawals()
          ->where('status', WithdrawalStatus::Pending->value)
          ->count(),
        'totalWithdrawalsCount' => $partner->withdrawals()->count()
      ]
      : null;

    return inertia('managers/dashboard', [
      'commissionBalance' => $commissionBalance,
      'attentionSummary' => $attentionSummary,
      'partnerProfile' => $partner
        ? [
          'id' => $partner->id,
          'name' => $partner->name,
          'canUpdate' => $user->isPartnerAdmin()
        ]
        : null,
      'partnerAnalytics' => $partnerAnalytics
    ]);
  }

  function updatePartnerProfile(Request $request)
  {
    $user = currentUser();

    abort_unless($user->isPartnerAdmin(), 403);

    $data = $request->validate([
      'name' => ['required', 'string', 'max:255']
    ]);

    $user
      ->partner()
      ->firstOrFail()
      ->update($data);

    return $this->ok();
  }

  function index(Request $request)
  {
    $query = User::query()
      ->whereHas('roles')
      ->with('roles', 'partner')
      ->withCount('partnerInstitutionGroups')
      ->orderByRaw('partner_institution_groups_count desc');
    return inertia('managers/home/list-managers', [
      'managers' => paginateFromRequest($query)
    ]);
  }

  function create()
  {
    return inertia('managers/home/create-manager');
  }

  function edit(User $user)
  {
    $user->load('roles', 'partner');
    return inertia('managers/home/create-manager', [
      'manager' => $user
    ]);
  }

  function store(Request $request)
  {
    $data = $request->validate(Partner::createRule());

    RecordPartner::make()->create($data);

    // $user = User::query()->create([
    //   ...collect($data)
    //     ->except('role', 'commission', 'referral_email', 'referral_commission')
    //     ->toArray(),
    //   'password' => bcrypt('password')
    // ]);
    // $user->assignRole($data['role']);

    // //= Create Partner's Record
    // if ($data['role'] === ManagerRole::Partner->value) {
    //   $refUser = User::where('email', $data['referral_email'])->first();

    //   Partner::create([
    //     'user_id' => $user->id,
    //     'commission' => $data['commission'],
    //     'referral_id' => $refUser?->partner?->id,
    //     'referral_commission' => $data['referral_commission'] ?? null
    //   ]);
    // }
    return $this->ok();
  }

  function update(User $user, Request $request)
  {
    $user->load('partner');
    $data = $request->validate(Partner::createRule($user));
    RecordPartner::make()->update($user, $data);
    // $user->update($data);
    return $this->ok();
  }

  function destroy(User $user)
  {
    abort_if(
      $user->partnerInstitutionGroups()->count() > 0,
      403,
      'This manager cannot be deleted because it is attached to an institution'
    );
    $user->delete();
    return $this->ok();
  }
}
