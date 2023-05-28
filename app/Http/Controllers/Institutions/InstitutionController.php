<?php
namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;

class InstitutionController extends Controller
{
  function index()
  {
    return inertia('institutions/dashboard');
  }
}
