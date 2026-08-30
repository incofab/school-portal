<?php

namespace App\Http\Controllers\API\Institutions;

use App\Actions\RecordAttendance;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
  public function selfStore(Institution $institution, Request $request)
  {
    $institutionUser = currentInstitutionUser();
    abort_unless(
      $institutionUser?->isStaff(),
      403,
      'You are not authorized to record attendance'
    );

    $request->merge(['institution_user_id' => $institutionUser->id]);
    $data = $request->validate([
      ...Attendance::createRule(),
      'datetime' => ['nullable', 'date']
    ]);

    $attendanceAt = filled($data['datetime'] ?? null)
      ? Carbon::parse($data['datetime'])
      : now();
    unset($data['datetime']);

    $res = (new RecordAttendance(
      $institution,
      $institutionUser,
      $data,
      $attendanceAt
    ))->run();

    return $this->apiRes($res, 401);
  }

  public function store(Institution $institution, Request $request)
  {
    $request->merge(['institution_user_id' => $request->code]);
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
