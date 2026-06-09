<?php

namespace App\Support\Audit;

use App\Models\ActivityLog;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogQuery
{
  public static function manager(): Builder
  {
    return ActivityLog::query()
      ->select('activity_logs.*')
      ->with('institution:id,uuid,name', 'institutionGroup:id,name');
  }

  public static function institution(Institution $institution): Builder
  {
    return self::manager()->where(function ($query) use ($institution) {
      $query
        ->where('activity_logs.institution_id', $institution->id)
        ->orWhere(function ($query) use ($institution) {
          $query
            ->whereNull('activity_logs.institution_id')
            ->where(
              'activity_logs.institution_group_id',
              $institution->institution_group_id
            );
        });
    });
  }
}
