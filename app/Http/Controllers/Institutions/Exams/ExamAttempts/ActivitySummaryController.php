<?php

namespace App\Http\Controllers\Institutions\Exams\ExamAttempts;

use App\Actions\Exams\GetExamAttemptActivitySummary;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Institution;
use Inertia\Inertia;

class ActivitySummaryController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function __invoke(
    Institution $institution,
    Event $event,
    GetExamAttemptActivitySummary $getExamAttemptActivitySummary
  ) {
    abort_unless($event->institution_id === $institution->id, 404);

    $summary = $getExamAttemptActivitySummary->execute($event);

    if (request()->expectsJson()) {
      return $this->ok($summary);
    }

    return Inertia::render(
      'institutions/exams/exam-attempts/activity-summary',
      [
        'event' => $event,
        'summary' => $summary
      ]
    );
  }
}
