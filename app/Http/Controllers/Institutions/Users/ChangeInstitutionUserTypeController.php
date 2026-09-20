<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\SecurityActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ChangeInstitutionUserTypeController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    InstitutionUser $institutionUser,
    SecurityActivityLogger $activityLogger
  ) {
    $data = $request->validate([
      'type' => ['required', new Enum(InstitutionUserType::class)]
    ]);

    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    abort_unless(
      (int) $institutionUser->institution_id === (int) $institution->id,
      404
    );

    $oldType = $institutionUser->type->value;
    ModelAudit::withoutAuditingFor(InstitutionUser::class, function () use (
      $institutionUser,
      $data
    ) {
      $institutionUser->update(['type' => $data['type']]);
    });
    $institutionUser->loadMissing('user');

    $activityLogger->userTypeChanged(
      currentUser(),
      $institutionUser,
      $institution,
      $oldType,
      $data['type']
    );

    return $this->ok();
  }
}
