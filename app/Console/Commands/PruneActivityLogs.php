<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneActivityLogs extends Command
{
  protected $signature = 'audit:prune
    {--dry-run : Count eligible logs without deleting them}
    {--category= : Limit pruning to normal, security, or financial logs}';

  protected $description = 'Prune activity logs according to configured retention windows.';

  public function handle(): int
  {
    $retentionDays = config('audit.retention_days', []);
    $categories = $this->option('category')
      ? [$this->option('category')]
      : array_keys($retentionDays);
    $total = 0;

    foreach ($categories as $category) {
      if (!array_key_exists($category, $retentionDays)) {
        $this->error("Unknown audit retention category: {$category}");

        return self::FAILURE;
      }

      $cutoff = Carbon::now()->subDays((int) $retentionDays[$category]);
      $query = ActivityLog::query()
        ->where('retention_category', $category)
        ->where('created_at', '<', $cutoff);
      $count = (clone $query)->count();
      $total += $count;

      if ($this->option('dry-run')) {
        $this->line(
          "{$category}: {$count} eligible before {$cutoff->toDateTimeString()}"
        );

        continue;
      }

      ActivityLog::withoutAppendOnly(function () use ($query) {
        $query->chunkById(500, function ($logs) {
          foreach ($logs as $log) {
            $log->delete();
          }
        });
      });

      $this->line(
        "{$category}: pruned {$count} logs before {$cutoff->toDateTimeString()}"
      );
    }

    $this->info("Audit pruning complete. {$total} log(s) matched.");

    return self::SUCCESS;
  }
}
