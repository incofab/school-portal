<?php

namespace App\Http\Controllers;

use App\Enums\ManagerRole;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class InstitutionRegistrationRequestController extends Controller
{
  public function create($partner = null)
  {
    if (!empty($partner)) {
      $partner = User::where('username', $partner)
        ->orWhere('id', $partner)
        ->first();
      $fileExists = file_exists(public_path("partners/$partner.webp"));
      // $imageUrl = $fileExists ? asset("partners/$partner.webp") : null;
    } else {
    }

    return inertia('auth/register', [
      'user' => $partner,
      // 'imageUrl' => $imageUrl ? $imageUrl : null
      'institutionGroup' => getInstitutionGroupFromDomain()
    ]);
  }

  /**
   * @param User $partner A partner whose referral link was used in creating this registration request
   */
  public function store(Request $request, User|null $partner = null)
  {
    if (!$partner?->isManager()) {
      $adminRole = Role::query()
        ->where('name', ManagerRole::Admin)
        ->firstOrFail();
      $partner = $adminRole->users()->firstOrFail();
    }

    $data = $request->validate([
      ...User::generalRule(),
      'institution' => ['required', 'array'],
      ...collect(Institution::generalRule('institution.'))
        ->except('institution.institution_group_id')
        ->toArray(),
      'reference' => [
        'required',
        'string',
        'unique:registration_requests,reference'
      ]
    ]);
    $data['password'] = bcrypt($data['password']);

    $registrationRequest = $partner->registrationRequests()->create([
      'reference' => $request->reference,
      'data' => collect($data)
        ->except('reference', 'password_confirmation')
        ->toArray()
    ]);

    return redirect(
      route('registration-requests.completed-message', [$registrationRequest])
    );
  }

  function registrationCompleted(RegistrationRequest $registrationRequest)
  {
    return Inertia::render('message', [
      'title' => 'Registration Received',
      'message' =>
        'Hi, <br><br>We have received your registration application. Our team will contact you shortly to proceed with the onboarding process. <br><br>Thank you.'
    ]);
  }
}
