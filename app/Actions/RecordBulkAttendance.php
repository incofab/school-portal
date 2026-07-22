<?php

namespace App\Actions;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\Res;
use Illuminate\Support\Str;

class RecordBulkAttendance
{
  /**
   * @param array {
   *  institution_user_ids: array<int>,
   *  unmark_institution_user_ids?: array<int>,
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
    $recordedCount = 0;
    $unmarkedCount = 0;
    $skippedCount = 0;
    $failedCount = 0;
    $selectedInstitutionUserIds = collect($this->post['institution_user_ids'])
      ->map(fn($id) => (int) $id)
      ->unique()
      ->values();
    $unmarkInstitutionUserIds = collect(
      $this->post['unmark_institution_user_ids'] ?? []
    )
      ->map(fn($id) => (int) $id)
      ->unique()
      ->values();

    foreach ($selectedInstitutionUserIds as $institutionUserId) {
      $payload = [
        'institution_user_id' => $institutionUserId,
        'type' => $this->post['type'],
        'remark' => $this->post['remark'] ?? null,
        'audit_suppress_individual' => true
        // 'force' => true
      ];

      if ($this->post['type'] === AttendanceType::In->value) {
        $payload['reference'] = (string) Str::orderedUuid()->toString();
      }

      $res = (new RecordAttendance(
        $this->institution,
        $this->staffInstitutionUser,
        $payload
      ))->run();

      if ($res->isNotSuccessful()) {
        $failedCount++;

        continue;
      }

      if (($res['status'] ?? null) === 'skipped') {
        $skippedCount++;

        continue;
      }

      $recordedCount++;
    }

    $unmarkedCount = $this->unmark($unmarkInstitutionUserIds->all());

    if ($recordedCount === 0 && $unmarkedCount === 0 && $failedCount > 0) {
      return failRes('No attendance records were updated.', [
        'recorded_count' => $recordedCount,
        'unmarked_count' => $unmarkedCount,
        'skipped_count' => $skippedCount,
        'failed_count' => $failedCount
      ]);
    }

    $message = "{$recordedCount} attendance record(s) updated.";
    if ($unmarkedCount > 0) {
      $message .= " {$unmarkedCount} unmarked.";
    }
    if ($skippedCount > 0) {
      $message .= " {$skippedCount} already handled.";
    }
    if ($failedCount > 0) {
      $message .= " {$failedCount} could not be updated.";
    }

    app(AcademicActivityLogger::class)->attendanceBulkUpdated(
      $this->institution,
      $this->staffInstitutionUser,
      [
        'type' => $this->post['type'],
        'selected_count' => $selectedInstitutionUserIds->count(),
        'unmarked_selected_count' => $unmarkInstitutionUserIds->count(),
        'recorded_count' => $recordedCount,
        'unmarked_count' => $unmarkedCount,
        'skipped_count' => $skippedCount,
        'failed_count' => $failedCount,
        'date' => now()->toDateString()
      ]
    );

    return successRes($message, [
      'recorded_count' => $recordedCount,
      'unmarked_count' => $unmarkedCount,
      'skipped_count' => $skippedCount,
      'failed_count' => $failedCount
    ]);
  }

  private function unmark(array $institutionUserIds): int
  {
    if (empty($institutionUserIds)) {
      return 0;
    }

    return match ($this->post['type']) {
      AttendanceType::In->value => $this->unmarkCheckIns($institutionUserIds),
      AttendanceType::Out->value => $this->unmarkCheckOuts($institutionUserIds),
      default => 0
    };
  }

  private function unmarkCheckIns(array $institutionUserIds): int
  {
    $today = now()->toDateString();

    return Attendance::query()
      ->where('institution_id', $this->institution->id)
      ->whereIn('institution_user_id', $institutionUserIds)
      ->whereDate('signed_in_at', $today)
      ->whereNull('signed_out_at')
      ->delete();
  }

  private function unmarkCheckOuts(array $institutionUserIds): int
  {
    $today = now()->toDateString();

    return Attendance::query()
      ->where('institution_id', $this->institution->id)
      ->whereIn('institution_user_id', $institutionUserIds)
      ->whereDate('signed_out_at', $today)
      ->update(['signed_out_at' => null]);
  }
}
