<?php

namespace App\Http\Controllers\Managers\PartnerRequests;

use App\Actions\RecordUsers\RecordPartner;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerRegistrationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PartnerRegistrationRequestsController extends Controller
{
  public function index()
  {
    $user = currentUser();
    !$user->isAdmin() && abort(400);

    $partnerRegistrationRequests = PartnerRegistrationRequest::with(
      'referral.user'
    );

    return Inertia::render(
      'managers/partner-registration-requests/list-partner-registration-requests',
      [
        'partnerRegistrationRequests' => paginateFromRequest(
          $partnerRegistrationRequests
        )
      ]
    );
  }
  
  function onboardPartner(Request $request, PartnerRegistrationRequest $partnerRegistrationRequest)
  {
    $data = $request->validate(Partner::partnerOnlyRule());

    RecordPartner::make()->createFromPartnerRequest($partnerRegistrationRequest, $data);

    return $this->ok();
  }

  public function destroy(
    PartnerRegistrationRequest $partnerRegistrationRequest
  ) {
    $partnerRegistrationRequest->delete();
    return $this->ok();
  }
}
