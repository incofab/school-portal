<?php

namespace App\Http\Controllers\Managers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Inertia\Inertia;

class ShowInstitutionController extends Controller
{
  public function __invoke(Institution $institution)
  {
    return Inertia::render('managers/institutions/show-institution', [
      'institution' => $institution
    ]);
  }
}
