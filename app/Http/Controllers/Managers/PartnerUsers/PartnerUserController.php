<?php

namespace App\Http\Controllers\Managers\PartnerUsers;

use App\Enums\ManagerRole;
use App\Enums\PartnerUserRole;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;

class PartnerUserController extends Controller
{
  private Partner $adminPartner;

  function __construct()
  {
    $this->middleware(function ($request, $next) {
      $partnerUser = currentUser()
        ?->partnerUser()
        ->with('partner')
        ->first();

      abort_unless(
        !$partnerUser || $partnerUser?->role === PartnerUserRole::Admin,
        403,
        'Only partner admins can manage partner users'
      );

      $this->adminPartner = $partnerUser->partner;
      return $next($request);
    });
  }

  public function index()
  {
    return inertia('managers/partner-users/list-partner-users', [
      'partnerUsers' => paginateFromRequest(
        $this->adminPartner
          ->partnerUsers()
          ->with('user.roles')
          ->latest('id')
      )
    ]);
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      ...User::generalRule(),
      'username' => [
        'required',
        'unique:users,username',
        function ($attr, $value, $fail) {
          if (ctype_digit($value)) {
            $fail('Username cannot contain only digits');
          }
        }
      ],
      'role' => ['required', new Enum(PartnerUserRole::class)]
    ]);

    DB::transaction(function () use ($data) {
      $user = User::query()->create([
        ...collect($data)
          ->except('role', 'password_confirmation')
          ->toArray(),
        'password' => Hash::make($data['password'])
      ]);
      $user->assignRole(ManagerRole::Partner);

      $this->adminPartner->partnerUsers()->create([
        'user_id' => $user->id,
        'role' => $data['role']
      ]);
    });

    return $this->ok();
  }

  public function update(Request $request, PartnerUser $partnerUser)
  {
    abort_unless(
      $partnerUser->partner_id === $this->adminPartner->id,
      403,
      'You cannot update users outside your partner account'
    );

    $data = $request->validate([
      'role' => ['required', new Enum(PartnerUserRole::class)]
    ]);

    $currentRole = $partnerUser->role->value;

    if (
      $data['role'] === PartnerUserRole::Staff->value &&
      $currentRole === PartnerUserRole::Admin->value &&
      $this->adminPartner
        ->partnerUsers()
        ->where('role', PartnerUserRole::Admin->value)
        ->count() <= 1
    ) {
      return response()->json(
        [
          'message' => 'A partner account must have at least one admin.',
          'errors' => [
            'role' => ['A partner account must have at least one admin.']
          ]
        ],
        422
      );
    }

    abort_if(
      $partnerUser->user_id === currentUser()?->id,
      403,
      'You cannot update your own role'
    );

    $partnerUser->update(['role' => $data['role']]);

    return $this->ok();
  }
}
