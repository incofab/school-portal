<?php

namespace App\Http\Controllers\API\Institutions;

use App\Actions\RecordAttendance;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Institution;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
  public function store(Institution $institution, Request $request)
  {
    $data = $request->validate(Attendance::createRule());
    $staffInstitutionUser = currentInstitutionUser();
    abort_unless(
      $staffInstitutionUser->isStaff(),
      403,
      'You are not authorized to record attendance'
    );
    $res = (new RecordAttendance(
      $institution,
      $staffInstitutionUser,
      $data
    ))->run();
    return $this->apiRes($res, 401);
  }
}
