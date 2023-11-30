<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotActivatedErrorController extends Controller
{
  public function __invoke(Request $request)
  {
    return inertia('institutions/exams/external/not-activated-error-page', []);
  }
}
