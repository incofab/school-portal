<?php

namespace App\Http\Controllers\Managers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;

class DeleteInstitutionController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    $this->authorize('delete', $institution);

    abort_if(
      $institution->classifications()->count() > 0,
      403,
      'This institution contains some classes'
    );
    abort_if(
      $institution->tokenUsers()->count() > 0,
      403,
      'This institution contains some token users'
    );
    abort_if(
      $institution->courses()->count() > 0,
      403,
      'This institution contains some token subjects'
    );
    $institution->delete();
    return $this->ok();
  }
}
