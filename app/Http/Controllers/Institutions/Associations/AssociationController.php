<?php

namespace App\Http\Controllers\Institutions\Associations;

use Inertia\Inertia;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Association;

class AssociationController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Request $request, Institution $institution)
  {
    return Inertia::render('institutions/associations/list-associations', [
      'associations' => Association::all()
    ]);
  }

  public function store(Request $request, Institution $institution)
  {
    $validatedData = $request->validate(Association::createRule($institution));
    $institution->associations()->create($validatedData);

    return $this->ok();
  }

  public function update(
    Request $request,
    Institution $institution,
    Association $association
  ) {
    $validatedData = $request->validate(
      Association::createRule($institution, $association)
    );
    $association->fill($validatedData)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, Association $association)
  {
    $association->delete();
    return $this->ok();
  }
}
