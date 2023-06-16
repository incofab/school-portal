<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;

class SearchInstitutionController extends Controller
{
  public function __invoke(Request $request)
  {
    $query = Institution::query()
      ->select('institutions.*')
      ->when($request->search, fn($q, $value) => $q->where('name', $value));

    return response()->json([
      'result' => paginateFromRequest($query->latest('institutions.id'))
    ]);
  }
}
