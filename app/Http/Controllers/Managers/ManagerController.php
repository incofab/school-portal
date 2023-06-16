<?php
namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
  function index(Request $request)
  {
    return inertia('managers/dashboard');
  }
}
