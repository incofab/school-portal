<?php
namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;

class CreateInstitutionController extends Controller
{
  function create()
  {
    return inertia('institutions/create-institution');
  }

  public function store(Request $request)
  {
    $data = $request->validate(Institution::generalRule());

    $institution = currentUser()
      ->institutions()
      ->create($data);

    return response()->json(['createdInstitution' => $institution]);
  }
}
