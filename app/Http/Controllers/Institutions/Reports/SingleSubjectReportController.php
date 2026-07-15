<?php

namespace App\Http\Controllers\Institutions\Reports;

use App\Actions\CourseResult\GenerateSingleSubjectReport;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SingleSubjectReportRequest;
use App\Models\Institution;

class SingleSubjectReportController extends Controller
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
    SingleSubjectReportRequest $request
  ) {
    $classification = $request->classificationObj;
    $academicSession = $request->academicSessionObj;
    $course = $request->courseObj;

    $singleSubjectReport = [];
    if ($classification && $academicSession && $course) {
      $singleSubjectReport = GenerateSingleSubjectReport::run(
        $classification,
        $academicSession,
        $course,
        false
      );
    }

    return inertia('institutions/reports/single-subject-report-sheet', [
      'classification' => $classification,
      'academicSession' => $academicSession,
      'course' => $course,
      'singleSubjectReport' => $singleSubjectReport
    ]);
  }
}
