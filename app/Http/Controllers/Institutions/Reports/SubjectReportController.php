<?php

namespace App\Http\Controllers\Institutions\Reports;

use App\Actions\CourseResult\GenerateSubjectReport;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectReportRequest;
use App\Models\Institution;

class SubjectReportController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin, InstitutionUserType::Teacher]);
  }

  public function __invoke(
    Institution $institution,
    SubjectReportRequest $request
  ) {
    $classification = $request->classificationObj;
    $academicSession = $request->academicSessionObj;
    $term = $request->term;

    $reportRows = [];
    $reportSections = [];
    if ($classification && $academicSession && $term) {
      $reportSections = GenerateSubjectReport::runSections(
        $classification,
        $academicSession,
        $term,
        false
      );
      $reportRows = $reportSections[0]['subjectReport'] ?? [];
    }

    return inertia('institutions/reports/subject-report-sheet', [
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term,
      'subjectReport' => $reportRows,
      'subjectReportSections' => $reportSections
    ]);
  }
}
