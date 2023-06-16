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
        Institution::query()->latest('institutions.id')
      )
    ]);
  }
}
