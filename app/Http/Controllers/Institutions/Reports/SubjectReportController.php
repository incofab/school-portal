<?php

namespace App\Http\Controllers\Institutions\Reports;

use App\Actions\CourseResult\GenerateSubjectReport;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectReportRequest;
use App\Models\AcademicSession;
use App\Models\Institution;

class SubjectReportController extends Controller
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
    SubjectReportRequest $request
  ) {
    $classification = $request->classificationObj;
    $academicSession = $request->academicSessionObj;
    $term = $request->term;

    $reportRows = [];
    if ($classification && $academicSession && $term) {
      $reportRows = GenerateSubjectReport::run(
        $classification,
        $academicSession,
        $term,
        false
      );
    }

    return inertia('institutions/reports/subject-report-sheet', [
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term,
      'subjectReport' => $reportRows,
      'academicSessions' => AcademicSession::all(),
      'classifications' => $institution->classifications()->get()
    ]);
  }
}
