<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;

class NotActivatedErrorController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    return inertia('institutions/exams/external/not-activated-error-page', []);
  }
}
