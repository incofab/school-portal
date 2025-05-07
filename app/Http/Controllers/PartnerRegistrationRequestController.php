<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\PartnerRegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;

class PartnerRegistrationRequestController extends Controller
{
  public function create(Institution $institution)
  {
    return inertia('auth/register-partner');
  }

  public function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      ...User::generalRule(),
      'username' => ['required', 'string', 'max:255', 'unique:users,username'],
      'referral_email' => ['nullable', 'string', 'email', 'exists:users,email'],
      'reference' => [
        'required',
        'string',
        'unique:partner_registration_requests,reference'
      ]
    ]);

    //= Confirm that the referral email belongs to a Partner
    $refEmail = $data['referral_email'];
    $refId = null; //Default

    if (!empty($refEmail)) {
      $refUser = User::where('email', $refEmail)->first();
      if (!$refUser->isPartner()) {
        return $this->message(
          'The Referral Email does NOT belong to an existing Partner.',
          401
        );
      }

      $refId = $refUser->partner->id;
    }

    //= Create Record
    PartnerRegistrationRequest::create([
      ...collect($data)
        ->except('referral_email')
        ->toArray(),
      'referral_id' => $refId
    ]);

    return $this->ok();
  }
}
