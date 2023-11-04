<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionsController extends Controller
{
  public function index(Request $request)
  {
    return Inertia::render('managers/institutions/list-institutions', [
      'institutions' => paginateFromRequest(
        Institution::query()
          ->withCount('classifications')
          ->latest('institutions.id')
      )
    ]);
  }

  public function destroy(Request $request, Institution $institution)
  {
    abort_if(
      $institution->classifications()->count() > 0,
      403,
      'This institution contains some classes'
    );
    $institution->delete();
    return $this->ok();
  }
}
