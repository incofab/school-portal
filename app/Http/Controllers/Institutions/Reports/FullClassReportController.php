<?php

namespace App\Http\Controllers\Institutions\Reports;

use App\Actions\CourseResult\GenerateFullClassReport;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FullClassReportRequest;
use App\Models\Institution;

class FullClassReportController extends Controller
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
    FullClassReportRequest $request
  ) {
    $classification = $request->classificationObj;
    $academicSession = $request->academicSessionObj;
    $term = $request->termObj;

    $fullClassReport = GenerateFullClassReport::empty();

    if ($classification && $academicSession && $term) {
      $fullClassReport = GenerateFullClassReport::run(
        $classification,
        $academicSession,
        $term
      );
    }

    return inertia('institutions/reports/full-class-report-sheet', [
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term?->value,
      'fullClassReport' => $fullClassReport
    ]);
  }
}
