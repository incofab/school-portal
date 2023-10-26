<?php

namespace App\Http\Middleware;

use App\Models\AcademicSession;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
  /**
   * The root template that's loaded on the first page visit.
   *
   * @see https://inertiajs.com/server-side-setup#root-template
   * @var string
   */
  protected $rootView = 'app';

  /**
   * Determines the current asset version.
   *
   * @see https://inertiajs.com/asset-versioning
   * @param  \Illuminate\Http\Request  $request
   * @return string|null
   */
  public function version(Request $request): ?string
  {
    return parent::version($request);
  }

  /**
   * Defines the props that are shared by default.
   *
   * @see https://inertiajs.com/shared-data
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function share(Request $request): array
  {
    $settingHandler = SettingsHandler::makeFromRoute();
    $academicSessionId = $settingHandler->getCurrentAcademicSession();

    return array_merge(parent::share($request), [
      'shared__isImpersonating' => session()->has('impersonator_id'),
      'shared__currentUser' => currentUser(),
      'shared__currentInstitution' => fn() => currentInstitution(),
      'shared__currentInstitutionUser' => fn() => currentInstitutionUser(),
      'shared__currentTerm' => $settingHandler->getCurrentTerm(),
      'shared__currentAcademicSessionId' => $academicSessionId,
      'shared__currentAcademicSession' => AcademicSession::query()->find(
        $academicSessionId
      )
    ]);
  }
}
