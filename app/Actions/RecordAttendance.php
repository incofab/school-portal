<?php
namespace App\Actions;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Support\Res;

class RecordAttendance
{
  /**
   * @param array {
   *  institution_user_id: number,
   *  reference?: string, // required if type is 'in'
   *  remark?: string,
   *  type: string,
   * } $post
   */
  function __construct(
    private Institution $institution,
    private InstitutionUser $staffInstitutionUser,
    private array $post
  ) {
  }

  public function run(): Res
  {
    if ($this->post['type'] === AttendanceType::In->value) {
      return $this->checkIn();
    } else {
      return $this->checkOut();
    }
  }

  function checkIn(): Res
  {
    $now = now();
    $attendance = $this->institution
      ->attendances()
      ->whereDate('signed_in_at', $now->toDateString())
      ->where('institution_user_id', $this->post['institution_user_id'])
      ->first();
    if ($attendance) {
      return successRes('User already signed in today.');
    }
    Attendance::create([
      ...collect($this->post)->except('type'),
      'institution_id' => $this->institution->id,
      'institution_staff_user_id' => $this->staffInstitutionUser->id,
      'signed_in_at' => now()
    ]);
    return successRes();
  }

  function checkOut(): Res
  {
    $lastSignIn = Attendance::where(
      'institution_user_id',
      $this->post['institution_user_id']
    )
      ->whereNull('signed_out_at')
      ->latest()
      ->first();

    if (!$lastSignIn) {
      return failRes('No Signed-In Record Found.');
    }

    $lastSignIn
      ->fill([
        'remark' => $lastSignIn->remark . ' ' . ($this->post['remark'] ?? ''),
        'signed_out_at' => now()
      ])
      ->save();
    return successRes();
  }
}
