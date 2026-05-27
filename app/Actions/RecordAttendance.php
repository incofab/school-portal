<?php
namespace App\Actions;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Support\SettingsHandler;
use App\Support\Res;
use Illuminate\Support\Carbon;

class RecordAttendance
{
  /**
   * @param array {
   *  institution_user_id: number,
   *  force?: bool,
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
    $activeDayCheck = $this->ensureActiveDay();
    if ($activeDayCheck->isNotSuccessful()) {
      return $activeDayCheck;
    }

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
      if ($this->post['force'] ?? false) {
        $attendance
          ->fill([
            'remark' => $this->post['remark'] ?? $attendance->remark,
            'institution_staff_user_id' => $this->staffInstitutionUser->id,
            'signed_in_at' => $now
          ])
          ->save();

        return successRes('', ['status' => 'recorded']);
      }

      return successRes('User already signed in today.', [
        'status' => 'skipped'
      ]);
    }
    Attendance::create([
      ...collect($this->post)->except('type', 'force'),
      'institution_id' => $this->institution->id,
      'institution_staff_user_id' => $this->staffInstitutionUser->id,
      'signed_in_at' => now()
    ]);
    return successRes('', ['status' => 'recorded']);
  }

  function checkOut(): Res
  {
    $todaySignOut = Attendance::where(
      'institution_user_id',
      $this->post['institution_user_id']
    )
      ->whereDate('signed_out_at', now()->toDateString())
      ->latest()
      ->first();

    if ($todaySignOut && ($this->post['force'] ?? false)) {
      $todaySignOut
        ->fill([
          'remark' => array_key_exists('remark', $this->post)
            ? $this->post['remark']
            : $todaySignOut->remark,
          'institution_staff_user_id' => $this->staffInstitutionUser->id,
          'signed_out_at' => now()
        ])
        ->save();

      return successRes('', ['status' => 'recorded']);
    }

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
        'remark' => $this->appendRemark($lastSignIn->remark),
        'institution_staff_user_id' => $this->staffInstitutionUser->id,
        'signed_out_at' => now()
      ])
      ->save();
    return successRes('', ['status' => 'recorded']);
  }

  private function appendRemark(?string $existingRemark): string
  {
    return trim(
      trim($existingRemark ?? '') . ' ' . ($this->post['remark'] ?? '')
    );
  }

  private function ensureActiveDay(): Res
  {
    $termDetail = SettingsHandler::makeFromRoute()->fetchCurrentTermDetail();
    $today = Carbon::now();

    if (!$termDetail->isActiveOnDate($today)) {
      return failRes('Attendance can only be recorded on active school days.');
    }

    return successRes();
  }
}
