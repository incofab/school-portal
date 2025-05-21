<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TermDetail;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;

class TermDetailController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(
    Institution $institution,
    ?TermDetail $termDetail = null
  ) {
    $termDetail =
      $termDetail ?? SettingsHandler::makeFromRoute()->fetchCurrentTermDetail();
    $termDetail->load('academicSession');
    $query = TermDetail::query()
      ->with('academicSession')
      ->latest('academic_session_id')
      ->latest('term');
    return inertia('institutions/term-details/list-term-details', [
      'termDetail' => $termDetail,
      'termDetails' => paginateFromRequest($query)
    ]);
  }

  public function update(
    Request $request,
    Institution $institution,
    TermDetail $termDetail
  ) {
    $data = $request->validate([
      'expected_attendance_count' => ['nullable', 'integer'],
      'start_date' => ['nullable', 'date'],
      'end_date' => ['nullable', 'date']
    ]);
    $termDetail->fill($data)->save();
    return $this->ok();
  }
}
