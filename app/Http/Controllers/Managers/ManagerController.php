<?php
namespace App\Http\Controllers\Managers;

use App\Actions\RecordUsers\RecordPartner;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
  function dashboard(Request $request)
  {
    $user = currentUser();
    $commissionBalance = $user->isPartner() ? $user->partner->wallet : 0;

    return inertia('managers/dashboard', [
      'commissionBalance' => $commissionBalance
    ]);
  }

  function index(Request $request)
  {
    $query = User::query()
      ->whereHas('roles')
      ->with('roles')
      ->withCount('partnerInstitutionGroups');
    return inertia('managers/home/list-managers', [
      'managers' => paginateFromRequest($query)
    ]);
  }

  function create()
  {
    return inertia('managers/home/create-manager');
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
    $data = $request->validate(User::generalRule($user->id));
    $user->update($data);
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
