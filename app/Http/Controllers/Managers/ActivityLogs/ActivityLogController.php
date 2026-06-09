<?php

namespace App\Http\Controllers\Managers\ActivityLogs;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Support\Audit\ActivityLogExporter;
use App\Support\Audit\ActivityLogQuery;
use App\Support\UITableFilters\ActivityLogUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
  public function __invoke(Request $request)
  {
    $this->authorize('viewAnyManager', ActivityLog::class);

    $query = $this->filteredQuery($request);

    $query->when(!$request->sortKey, fn($q) => $q->latest('activity_logs.id'));

    return Inertia::render('managers/activity-logs/list-activity-logs', [
      'activityLogs' => paginateFromRequest($query),
      'institutions' => Institution::query()
        ->select('id', 'uuid', 'name')
        ->orderBy('name')
        ->get(),
      'institutionGroups' => InstitutionGroup::query()
        ->select('id', 'name')
        ->orderBy('name')
        ->get(),
      'filterOptions' => $this->filterOptions(),
      'canExport' => $request->user()->can('exportManager', ActivityLog::class)
    ]);
  }

  public function export(Request $request)
  {
    $this->authorize('exportManager', ActivityLog::class);

    return ActivityLogExporter::download(
      $this->filteredQuery($request),
      'activity-logs.csv'
    );
  }

  private function filteredQuery(Request $request)
  {
    $query = ActivityLogQuery::manager();

    ActivityLogUITableFilters::make($request->all(), $query)->filterQuery();

    return $query;
  }

  private function filterOptions(): array
  {
    return [
      'categories' => ActivityLogCategory::values(),
      'severities' => ActivityLogSeverity::values(),
      'retentionCategories' => ['normal', 'security', 'financial']
    ];
  }
}
