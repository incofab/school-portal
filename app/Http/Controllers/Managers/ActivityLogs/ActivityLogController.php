<?php

namespace App\Http\Controllers\Managers\ActivityLogs;

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
    public function __invoke(Request $request)
    {
        $this->authorize('viewAnyManager', ActivityLog::class);

        $query = ActivityLog::query()
            ->select('activity_logs.*')
            ->with('institution:id,uuid,name', 'institutionGroup:id,name');

        ActivityLogUITableFilters::make($request->all(), $query)->filterQuery();

        $query->when(! $request->sortKey, fn ($q) => $q->latest('activity_logs.id'));

        return Inertia::render('managers/activity-logs/list-activity-logs', [
            'activityLogs' => paginateFromRequest($query),
            'institutions' => Institution::query()
                ->select('id', 'uuid', 'name')
                ->orderBy('name')
                ->get(),
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    private function filterOptions(): array
    {
        return [
            'categories' => ActivityLogCategory::values(),
            'severities' => ActivityLogSeverity::values(),
        ];
    }
}
