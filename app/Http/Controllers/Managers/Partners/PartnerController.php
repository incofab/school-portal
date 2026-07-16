<?php

namespace App\Http\Controllers\Managers\Partners;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerController extends Controller
{
  public function index()
  {
    return inertia('managers/partners/list-partners', [
      'partners' => paginateFromRequest(
        Partner::query()
          ->with(['user', 'adminUsers', 'referral.user'])
          ->withCount('partnerUsers')
          ->latest('id')
      )
    ]);
  }

  public function update(Request $request, Partner $partner)
  {
    $validator = Validator::make($request->all(), [
      'name' => ['required', 'string', 'max:255'],
      'commission' => ['required', 'numeric', 'min:0'],
      'referral_email' => ['nullable', 'exists:users,email'],
      'referral_commission' => ['nullable', 'numeric', 'min:0']
    ]);

    $validator->after(function ($validator) use ($request, $partner) {
      if (!$request->filled('referral_email')) {
        return;
      }

      $referralUser = User::query()
        ->where('email', $request->input('referral_email'))
        ->with('partner')
        ->first();

      if (!$referralUser?->partner) {
        $validator
          ->errors()
          ->add('referral_email', 'Referral email must belong to a partner account.');
        return;
      }

      if ($referralUser->partner->id === $partner->id) {
        $validator
          ->errors()
          ->add('referral_email', 'A partner account cannot refer itself.');
      }
    });

    $data = $validator->validate();

    $referralId = null;
    if (!empty($data['referral_email'])) {
      $referralUser = User::query()
        ->where('email', $data['referral_email'])
        ->with('partner')
        ->first();

      $referralId = $referralUser->partner->id;
    }

    $partner->update([
      'name' => $data['name'],
      'commission' => $data['commission'],
      'referral_id' => $referralId,
      'referral_commission' => $data['referral_commission'] ?? 0
    ]);

    return $this->ok();
  }
}
