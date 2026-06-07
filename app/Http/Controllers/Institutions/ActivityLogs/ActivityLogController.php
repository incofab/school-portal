<?php

namespace App\Http\Controllers\Institutions\ActivityLogs;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Institution;
use App\Support\UITableFilters\ActivityLogUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
  public function __invoke(Institution $institution, Request $request)
  {
    $this->authorize('viewAnyInstitution', ActivityLog::class);

    $query = ActivityLog::query()
      ->select('activity_logs.*')
      ->where('activity_logs.institution_id', $institution->id)
      ->with('institution:id,uuid,name', 'institutionGroup:id,name');

    ActivityLogUITableFilters::make(
      $request->except('institution_id'),
      $query
    )->filterQuery();

    $query->when(!$request->sortKey, fn($q) => $q->latest('activity_logs.id'));

    return Inertia::render('institutions/activity-logs/list-activity-logs', [
      'activityLogs' => paginateFromRequest($query),
      'filterOptions' => [
        'categories' => ActivityLogCategory::values(),
        'severities' => ActivityLogSeverity::values()
      ]
    ]);
  }
}
