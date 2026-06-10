<?php

namespace App\Actions;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\Audit\ModelAudit;
use App\Support\Res;
use App\Support\SettingsHandler;
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
  public function __construct(
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

  public function checkIn(): Res
  {
    $now = now();
    $attendance = $this->institution
      ->attendances()
      ->whereDate('signed_in_at', $now->toDateString())
      ->where('institution_user_id', $this->post['institution_user_id'])
      ->first();
    if ($attendance) {
      if ($this->post['force'] ?? false) {
        $oldValues = [
          'remark' => $attendance->remark,
          'institution_staff_user_id' => $attendance->institution_staff_user_id,
          'signed_in_at' => $attendance->signed_in_at
        ];
        ModelAudit::withoutAuditingFor(Attendance::class, function () use (
          $attendance,
          $now
        ) {
          $attendance
            ->fill([
              'remark' => $this->post['remark'] ?? $attendance->remark,
              'institution_staff_user_id' => $this->staffInstitutionUser->id,
              'signed_in_at' => $now
            ])
            ->save();
        });
        $this->logAttendanceUpdated($attendance, $oldValues);

        return successRes('', ['status' => 'recorded']);
      }

      return successRes('User already signed in today.', [
        'status' => 'skipped'
      ]);
    }
    $attendance = ModelAudit::withoutAuditingFor(
      Attendance::class,
      fn() => Attendance::create([
        ...collect($this->post)->except(
          'type',
          'force',
          'audit_suppress_individual'
        ),
        'institution_id' => $this->institution->id,
        'institution_staff_user_id' => $this->staffInstitutionUser->id,
        'signed_in_at' => now()
      ])
    );
    $this->logAttendanceRecorded($attendance);

    return successRes('', ['status' => 'recorded']);
  }

  public function checkOut(): Res
  {
    $todaySignOut = Attendance::where(
      'institution_user_id',
      $this->post['institution_user_id']
    )
      ->whereDate('signed_out_at', now()->toDateString())
      ->latest()
      ->first();

    if ($todaySignOut && ($this->post['force'] ?? false)) {
      $oldValues = [
        'remark' => $todaySignOut->remark,
        'institution_staff_user_id' => $todaySignOut->institution_staff_user_id,
        'signed_out_at' => $todaySignOut->signed_out_at
      ];
      ModelAudit::withoutAuditingFor(Attendance::class, function () use (
        $todaySignOut
      ) {
        $todaySignOut
          ->fill([
            'remark' => array_key_exists('remark', $this->post)
              ? $this->post['remark']
              : $todaySignOut->remark,
            'institution_staff_user_id' => $this->staffInstitutionUser->id,
            'signed_out_at' => now()
          ])
          ->save();
      });
      $this->logAttendanceUpdated($todaySignOut, $oldValues);

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

    $oldValues = [
      'remark' => $lastSignIn->remark,
      'institution_staff_user_id' => $lastSignIn->institution_staff_user_id,
      'signed_out_at' => $lastSignIn->signed_out_at
    ];
    ModelAudit::withoutAuditingFor(Attendance::class, function () use (
      $lastSignIn
    ) {
      $lastSignIn
        ->fill([
          'remark' => $this->appendRemark($lastSignIn->remark),
          'institution_staff_user_id' => $this->staffInstitutionUser->id,
          'signed_out_at' => now()
        ])
        ->save();
    });
    $this->logAttendanceUpdated($lastSignIn, $oldValues);

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

  private function logAttendanceRecorded(Attendance $attendance): void
  {
    if ($this->post['audit_suppress_individual'] ?? false) {
      return;
    }

    app(AcademicActivityLogger::class)->attendanceRecorded($attendance);
  }

  private function logAttendanceUpdated(
    Attendance $attendance,
    array $oldValues
  ): void {
    if ($this->post['audit_suppress_individual'] ?? false) {
      return;
    }

    app(AcademicActivityLogger::class)->attendanceUpdated(
      $attendance,
      $oldValues
    );
  }
}
