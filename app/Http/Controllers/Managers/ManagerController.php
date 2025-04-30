<?php
namespace App\Http\Controllers\Managers;

use App\Enums\ManagerRole;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ManagerController extends Controller
{
  function dashboard(Request $request)
  {
    $user = currentUser();
    $commissionBalance = $user->isPartner()? $user->partner->wallet : 0;

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
    $data = $request->validate([
      ...User::generalRule(),
      'username' => ['required', 'unique:users,username'],
      'role' => [
        'required',
        new Enum(ManagerRole::class),
        function ($attr, $value, $fail) {
          if ($value === ManagerRole::Admin->value) {
            $fail('Admin role cannot be added through this form');
          }
        }
      ],
      'commission' => ['nullable', 'numeric', 'min:0'],
      'referral_email' => ['nullable', 'exists:users,email'],
      'referral_commission' => ['nullable', 'numeric', 'min:0']
    ]);

    $user = User::query()->create(
      collect($data)
        ->except('role', 'commission', 'referral_email', 'referral_commission')
        ->toArray()
    );

    $user->assignRole($data['role']);

    //= Create Partner's Record
    if ($data['role'] === ManagerRole::Partner->value) {
      $refUser = User::where('email', $data['referral_email'])->first();

      Partner::create([
        'user_id' => $user->id,
        'commission' => $data['commission'],
        'referral_user_id' => $refUser?->id,
        'referral_commission' => $data['referral_commission'] ?? null
      ]);
    }

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
