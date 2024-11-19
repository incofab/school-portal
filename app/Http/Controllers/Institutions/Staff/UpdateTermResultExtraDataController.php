<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TermResult;
use Illuminate\Http\Request;

class UpdateTermResultExtraDataController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  function __invoke(
    Request $request,
    Institution $institution,
    TermResult $termResult
  ) {
    $data = $request->validate([
      'weight' => ['nullable', 'numeric'],
      'height' => ['nullable', 'numeric'],
      'attendance_count' => ['nullable', 'integer']
    ]);
    $termResult->fill($data)->save();
    return $this->ok();
  }
}
