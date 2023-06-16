<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\PinGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PinGeneratorController extends Controller
{
  public function index(Request $request)
  {
    $query = PinGenerator::query()->with('institution', 'user');

    return Inertia::render('managers/pins/list-pin-generators', [
      'pinGenerators' => paginateFromRequest($query->latest('id'))
    ]);
  }
}
