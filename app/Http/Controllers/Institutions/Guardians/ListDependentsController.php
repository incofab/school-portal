<?php

namespace App\Http\Controllers\Institutions\Guardians;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListDependentsController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    $user = currentUser();
    abort_unless($user->isInstitutionGuardian(), 403);
    $query = $user
      ->dependents()
      ->getQuery()
      ->with('classification', 'user');

    return Inertia::render('institutions/guardians/list-dependents', [
      'dependents' => paginateFromRequest($query)
    ]);
  }
}
