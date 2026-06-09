<?php

namespace App\Http\Controllers\Institutions\ActivityLogs;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Institution;
use App\Support\Audit\ActivityLogExporter;
use App\Support\Audit\ActivityLogQuery;
use App\Support\UITableFilters\ActivityLogUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
  public function __invoke(Institution $institution, Request $request)
  {
    $this->authorize('viewAnyInstitution', ActivityLog::class);

    $query = $this->filteredQuery($institution, $request);

    $query->when(!$request->sortKey, fn($q) => $q->latest('activity_logs.id'));

    return Inertia::render('institutions/activity-logs/list-activity-logs', [
      'activityLogs' => paginateFromRequest($query),
      'filterOptions' => [
        'categories' => ActivityLogCategory::values(),
        'severities' => ActivityLogSeverity::values(),
        'retentionCategories' => ['normal', 'security', 'financial']
      ],
      'canExport' => $request
        ->user()
        ->can('exportInstitution', ActivityLog::class)
    ]);
  }

  public function export(Institution $institution, Request $request)
  {
    $this->authorize('exportInstitution', ActivityLog::class);

    return ActivityLogExporter::download(
      $this->filteredQuery($institution, $request),
      "{$institution->name}-activity-logs.csv"
    );
  }

  private function filteredQuery(Institution $institution, Request $request)
  {
    $query = ActivityLogQuery::institution($institution);

    ActivityLogUITableFilters::make(
      $request->except(['institution_id', 'institution_group_id']),
      $query
    )->filterQuery();

    return $query;
  }
}
