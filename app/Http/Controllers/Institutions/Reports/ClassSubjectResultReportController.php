<?php

namespace App\Http\Controllers\Institutions\Reports;

use App\Actions\CourseResult\GenerateClassSubjectResultReport;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassSubjectResultReportRequest;
use App\Models\Institution;

class ClassSubjectResultReportController extends Controller
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
    ClassSubjectResultReportRequest $request
  ) {
    $classification = $request->classificationObj;
    $academicSession = $request->academicSessionObj;

    $classSubjectResultReport = [
      'courses' => [],
      'students' => []
    ];

    if ($classification && $academicSession) {
      $classSubjectResultReport = GenerateClassSubjectResultReport::run(
        $classification,
        $academicSession,
        false
      );
    }

    return inertia('institutions/reports/class-subject-result-report-sheet', [
      'classification' => $classification,
      'academicSession' => $academicSession,
      'classSubjectResultReport' => $classSubjectResultReport
    ]);
  }
}
