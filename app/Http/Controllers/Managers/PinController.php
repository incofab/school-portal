<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Pin;
use App\Models\PinGenerator;
use App\Support\UITableFilters\PinUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PinController extends Controller
{
  public function index(Request $request, PinGenerator $pinGenerator = null)
  {
    $query = $pinGenerator ? $pinGenerator->pins()->getQuery() : Pin::query();
    $query = PinUITableFilters::make($request->all(), $query)
      ->filterQuery()
      ->getQuery()
      ->with('institution');

    return Inertia::render('managers/pins/list-pins', [
      'pins' => paginateFromRequest($query->latest('id')),
      'pinGenerator' => $pinGenerator?->load('user', 'institution')
    ]);
  }
}
