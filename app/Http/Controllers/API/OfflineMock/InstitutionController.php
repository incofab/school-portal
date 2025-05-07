<?php
namespace App\Http\Controllers\API\OfflineMock;

use App\Http\Controllers\Controller;
use App\Models\Institution;

class InstitutionController extends Controller
{
  function show(Institution $institution)
  {
    return $this->apiEmitResponse($institution);
  }
}
