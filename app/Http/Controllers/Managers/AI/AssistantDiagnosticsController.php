<?php

namespace App\Http\Controllers\Managers\AI;

use App\Http\Controllers\Controller;
use App\Models\AssistantRunMetric;
use App\Support\AI\AssistantDiagnostics;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssistantDiagnosticsController extends Controller
{
  public function __invoke(Request $request)
  {
    $this->authorize('viewAnyManager', AssistantRunMetric::class);

    $days = (int) $request->integer('days', 30);
    $diagnostics = AssistantDiagnostics::forDays($days ?: 30);

    return Inertia::render('managers/ai-assistant/diagnostics', [
      'summary' => $diagnostics->summary(),
      'feedback' => $diagnostics->feedback(),
      'outcomes' => $diagnostics->outcomes(),
      'failures' => $diagnostics->failuresByReason(),
      'byRole' => $diagnostics->byRole(),
      'institutions' => $diagnostics->busiestInstitutions(),
      'controls' => AssistantDiagnostics::operationalControls()
    ]);
  }
}
