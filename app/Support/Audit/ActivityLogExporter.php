<?php

namespace App\Support\Audit;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogExporter
{
  public static function download(
    Builder $query,
    string $filename
  ): StreamedResponse {
    return response()->streamDownload(
      function () use ($query) {
        $handle = fopen('php://output', 'w');

        fputcsv($handle, [
          'Time',
          'Institution',
          'Institution Group',
          'Actor',
          'Actor Role',
          'Category',
          'Event',
          'Subject Type',
          'Subject',
          'Severity',
          'Retention Category',
          'Description',
          'IP Address',
          'Request ID',
          'Impersonator',
          'Integrity Hash'
        ]);

        (clone $query)->reorder('activity_logs.id')->chunkById(
          500,
          function ($logs) use ($handle) {
            foreach ($logs as $log) {
              fputcsv($handle, [
                optional($log->created_at)->toDateTimeString(),
                $log->institution?->name,
                $log->institutionGroup?->name,
                $log->actor_name ?? 'System',
                $log->actor_role,
                $log->category,
                $log->event,
                class_basename((string) $log->subject_type),
                $log->subject_name,
                $log->severity,
                $log->retention_category,
                $log->description,
                $log->ip_address,
                $log->request_id,
                $log->impersonator_name ?? $log->impersonator_id,
                $log->row_hash
              ]);
            }
          },
          'activity_logs.id',
          'id'
        );

        fclose($handle);
      },
      sanitizeFilename($filename),
      [
        'Content-Type' => 'text/csv; charset=UTF-8'
      ]
    );
  }
}
